<?php

declare(strict_types=1);

namespace Terminal42\UrlRewriteBundle\Controller;

use Contao\CoreBundle\InsertTag\InsertTagParser;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Terminal42\UrlRewriteBundle\ConfigProvider\ConfigProviderInterface;
use Terminal42\UrlRewriteBundle\Exception\TemporarilyUnavailableConfigProviderException;
use Terminal42\UrlRewriteBundle\RewriteConfigInterface;
use Terminal42\UrlRewriteBundle\Routing\UrlRewriteLoader;

class RewriteController
{
    public function __construct(
        private readonly ConfigProviderInterface $configProvider,
        private readonly InsertTagParser $insertTagParser,
        private readonly ExpressionLanguage $expressionLanguage,
    ) {
    }

    /**
     * @throws RouteNotFoundException
     */
    public function indexAction(Request $request): Response
    {
        if (!$request->attributes->has(UrlRewriteLoader::ATTRIBUTE_NAME)) {
            throw new RouteNotFoundException(\sprintf('The "%s" attribute is missing', UrlRewriteLoader::ATTRIBUTE_NAME));
        }

        $rewriteId = $request->attributes->get(UrlRewriteLoader::ATTRIBUTE_NAME);

        try {
            $config = $this->configProvider->find((string) $rewriteId);
        } catch (TemporarilyUnavailableConfigProviderException) {
            return new Response(Response::$statusTexts[503], 503);
        }

        if (null === $config) {
            throw new RouteNotFoundException(\sprintf('URL rewrite config ID %s does not exist', $rewriteId));
        }

        $responseCode = $config->getResponseCode();

        if (410 === $responseCode) {
            return new Response(Response::$statusTexts[$responseCode], $responseCode);
        }

        try {
            $uri = $this->generateUri($request, $config);
        } catch (BadRequestException) {
            return new Response(Response::$statusTexts[400], 400);
        }

        if (null !== $uri) {
            return new RedirectResponse($uri, $responseCode);
        }

        return new Response(Response::$statusTexts[500], 500);
    }

    /**
     * Generate the URI.
     */
    private function generateUri(Request $request, RewriteConfigInterface $config): string|null
    {
        if (null === ($uri = $this->findBestUri($request, $config)) || $this->containsControlCharacters($uri)) {
            return null;
        }

        // Contao interprets raw < as the start of HTML when processing insert tags
        if (str_contains($uri, '{{') && str_contains($uri, '<')) {
            return null;
        }

        $uri = $this->replaceWildcards($request, $uri);
        $uri = $this->replaceInsertTags($uri);

        // Replace the multiple slashes except the ones for protocol
        $uri = preg_replace('@(?<!http:|https:|^)/+@', '/', $uri);

        // Make the URL absolute if it's not yet already
        if (!preg_match('@^(https?:)?//@', (string) $uri)) {
            $uri = $request->getSchemeAndHttpHost().$request->getBasePath().'/'.ltrim((string) $uri, '/');
        }

        if ($config->keepQueryParams()) {
            $uri .= '?'.http_build_query($request->query->all());
        }

        return $this->encodeUri($uri);
    }

    private function encodeUri(string $uri): string|null
    {
        // Reject invalid targets instead of stripping control characters from the URL
        if ($this->containsControlCharacters($uri)) {
            return null;
        }

        // Preserve URI delimiters and existing percent escapes when encoding unsafe bytes
        return preg_replace_callback(
            '~%(?![0-9a-fA-F]{2})|[^a-zA-Z0-9\-._\~:/?#\[\]@!$&\'()*+,;=%]~',
            static fn (array $matches): string => rawurlencode($matches[0]),
            $uri,
        );
    }

    private function containsControlCharacters(string $value): bool
    {
        return 1 === preg_match('/[\x00-\x1F\x7F]/', $value);
    }

    /**
     * Replace the wildcards.
     */
    private function replaceWildcards(Request $request, string $uri): string
    {
        $wildcards = [];

        // Get the route params wildcards
        foreach ($request->attributes->get('_route_params', []) as $k => $v) {
            $wildcards['{'.$k.'}'] = $v;
        }

        // Get the query wildcards
        foreach ($request->query->all() as $k => $v) {
            $wildcards['{'.$k.'}'] = $v;
        }

        return $this->substituteWildcards($uri, $wildcards);
    }

    /**
     * @param array<string, mixed> $wildcards
     */
    private function substituteWildcards(string $uri, array $wildcards): string
    {
        $flags = [];
        $result = '';
        $parts = preg_split('/(\{\{|\}\}|\{[^{}]+\})/', $uri, -1, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($parts as $index => $part) {
            if ('{{' === $part) {
                $flags[] = false;
            } elseif ('}}' === $part) {
                array_pop($flags);
            } elseif (1 === $index % 2 && $flags && !\array_key_exists($part, $wildcards)) {
                throw new BadRequestException('Missing redirect wildcard inside an insert tag');
            } elseif (1 === $index % 2 && \array_key_exists($part, $wildcards)) {
                $value = $this->getWildcardValue($wildcards[$part], str_contains($uri, '{{'));

                // Legacy hooks parse the rendered output of nested tags as part of the outer tag
                if (\count($flags) > 1) {
                    throw new BadRequestException('Redirect wildcards must not be used inside nested insert tags');
                }

                if ($flags && end($flags)) {
                    throw new BadRequestException('Redirect wildcards must not change insert tag flags');
                }

                if ($flags) {
                    $this->validateTagParameter($parts[$index - 1], $parts[$index + 1].($parts[$index + 2] ?? ''), $value);
                }

                $part = $value;
            } elseif ($flags && str_contains($part, '|')) {
                $flags[array_key_last($flags)] = true;
            }

            $result .= $part;
        }

        if (substr_count($result, '{{') !== substr_count($uri, '{{') || substr_count($result, '}}') !== substr_count($uri, '}}')) {
            throw new BadRequestException('Redirect wildcards must not create insert tag delimiters');
        }

        return $result;
    }

    private function getWildcardValue(mixed $value, bool $hasTags): string
    {
        if (!\is_scalar($value) && null !== $value) {
            throw new BadRequestException('Redirect wildcards require scalar values');
        }

        $value = (string) $value;

        // Contao preprocesses HTML attributes before resolving insert tags
        if ($this->containsControlCharacters($value) || false !== strpbrk($value, $hasTags ? '{}<>' : '{}')) {
            throw new BadRequestException('Invalid character in redirect wildcard');
        }

        return $value;
    }

    private function validateTagParameter(string $before, string $after, string $value): void
    {
        // Bind only complete positional or explicitly named values in the configured tag
        if (!preg_match('/^[^|{}]*(?<!:)::(?:((?:[^:=|{}]|:(?!:))+)=)?$/D', $before, $matches) || !preg_match('/^(?:::|\||\}\})/', $after)) {
            throw new BadRequestException('Redirect wildcards must fill an insert tag parameter value');
        }

        if (str_contains($value, '::') || str_contains($value, '|') || (str_ends_with($value, ':') && str_starts_with($after, '::')) || (!isset($matches[1]) && str_contains($value, '='))) {
            throw new BadRequestException('Redirect wildcards must not introduce insert tag syntax');
        }
    }

    /**
     * Replace the insert tags.
     */
    private function replaceInsertTags(string $uri): string
    {
        if (false === stripos($uri, '{{')) {
            return $uri;
        }

        return $this->insertTagParser->replaceInline($uri);
    }

    private function findBestUri(Request $request, RewriteConfigInterface $config): string|null
    {
        foreach ($config->getConditionalResponseUris() as $condition => $uri) {
            if ($this->expressionLanguage->evaluate($condition, ['request' => $request])) {
                return $uri;
            }
        }

        return $config->getResponseUri();
    }
}
