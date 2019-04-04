<?php

/*
 * UrlRewrite Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2019, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

namespace Terminal42\UrlRewriteBundle\Controller;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\InsertTags;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Terminal42\UrlRewriteBundle\ConfigProvider\ConfigProviderInterface;
use Terminal42\UrlRewriteBundle\RewriteConfigInterface;

class RewriteController
{
    /**
     * @var ConfigProviderInterface
     */
    private $configProvider;

    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * RewriteController constructor.
     *
     * @param ConfigProviderInterface  $configProvider
     * @param ContaoFrameworkInterface $framework
     */
    public function __construct(ConfigProviderInterface $configProvider, ContaoFrameworkInterface $framework)
    {
        $this->configProvider = $configProvider;
        $this->framework = $framework;
    }

    /**
     * Index action.
     *
     * @param Request $request
     *
     * @throws RouteNotFoundException
     *
     * @return Response
     */
    public function indexAction(Request $request): Response
    {
        if (!$request->attributes->has('_url_rewrite')) {
            throw new RouteNotFoundException('The _url_rewrite attribute is missing');
        }

        $rewriteId = $request->attributes->get('_url_rewrite');
        $config = $this->configProvider->find($rewriteId);

        if (null === $config) {
            throw new RouteNotFoundException(sprintf('URL rewrite config ID %s does not exist', $rewriteId));
        }

        $responseCode = $config->getResponseCode();

        if (410 === $responseCode) {
            return new Response(Response::$statusTexts[$responseCode], $responseCode);
        } elseif (null !== ($uri = $this->generateUri($request, $config))) {
            return new RedirectResponse($uri, $responseCode);
        }

        return new Response(Response::$statusTexts[500], 500);
    }

    /**
     * Generate the URI.
     *
     * @param Request                $request
     * @param RewriteConfigInterface $config
     *
     * @return string|null
     */
    private function generateUri(Request $request, RewriteConfigInterface $config): ?string
    {
        if (null === ($uri = $config->getResponseUri())) {
            return null;
        }

        $uri = $this->replaceWildcards($request, $uri);
        $uri = $this->replaceInsertTags($uri);

        // Replace the multiple slashes except the ones for protocol
        $uri = preg_replace('@(?<!http:|https:)/+@', '/', $uri);

        // Make the URL absolute if it's not yet already
        if (!preg_match('@^https?://@', $uri)) {
            $uri = $request->getSchemeAndHttpHost().$request->getBasePath().'/'.ltrim($uri, '/');
        }

        return $uri;
    }

    /**
     * Replace the wildcards.
     *
     * @param Request $request
     * @param string  $uri
     *
     * @return string
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
     *
     * @param string $uri
     *
     * @return string
     */
    private function replaceInsertTags(string $uri): string
    {
        if (false === stripos($uri, '{{')) {
            return $uri;
        }

        $this->framework->initialize();

        /** @var InsertTags $insertTags */
        $insertTags = $this->framework->createInstance(InsertTags::class);

        return $insertTags->replace($uri);
    }
}
