<?php

declare(strict_types=1);

namespace Terminal42\UrlRewriteBundle\Controller;

use Contao\CoreBundle\InsertTag\InsertTagParser;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
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

        if (null !== ($uri = $this->generateUri($request, $config))) {
            return new RedirectResponse($uri, $responseCode);
        }

        return new Response(Response::$statusTexts[500], 500);
    }

    /**
     * Generate the URI.
     */
    private function generateUri(Request $request, RewriteConfigInterface $config): string|null
    {
        if (null === ($uri = $this->findBestUri($request, $config))) {
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

        return $uri;
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

        return strtr($uri, $wildcards);
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
