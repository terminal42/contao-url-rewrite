<?php

/*
 * UrlRewrite Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2017, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

namespace Terminal42\UrlRewriteBundle\Controller;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Environment;
use Contao\InsertTags;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class RewriteController
{
    /**
     * @var Connection
     */
    private $db;

    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * RewriteController constructor.
     *
     * @param Connection               $db
     * @param ContaoFrameworkInterface $framework
     */
    public function __construct(Connection $db, ContaoFrameworkInterface $framework)
    {
        $this->db = $db;
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
        if (!$request->attributes->has('_url_rewrite') || !($rewriteId = $request->attributes->getInt('_url_rewrite'))) {
            throw new RouteNotFoundException('There _url_rewrite attribute is missing');
        }

        $config = $this->db->fetchAssoc('SELECT * FROM tl_url_rewrite WHERE id=?', [$rewriteId]);

        if (false === $config || !isset($config['responseCode'])) {
            throw new RouteNotFoundException(sprintf('URL rewrite config ID %s does not exist', $rewriteId));
        }

        $responseCode = (int) $config['responseCode'];

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
     * @param Request $request
     * @param array   $config
     *
     * @return string|null
     */
    private function generateUri(Request $request, array $config): ?string
    {
        if (!isset($config['responseUri'])) {
            return null;
        }

        $uri = $config['responseUri'];

        // Replace the wildcards
        if ($request->attributes->has('_route_params')) {
            $wildcards = [];

            // Replace the wildcards
            foreach ($request->attributes->get('_route_params') as $k => $v) {
                $wildcards['{'.$k.'}'] = $v;
            }

            $uri = strtr($uri, $wildcards);
        }

        // Replace the insert tags
        if (stripos($uri, '{{') !== false) {
            $this->framework->initialize();

            /** @var InsertTags $insertTags */
            $insertTags = $this->framework->createInstance(InsertTags::class);

            $uri = $insertTags->replace($uri);
        }

        // Replace the multiple slashes except the ones for protocol
        $uri = preg_replace('@(?<!http:|https:)/+@', '/', $uri);

        // Make the URL absolute if it's not yet already
        if (!preg_match('@^https?://@', $uri)) {
            $this->framework->initialize();
            $uri = $this->framework->getAdapter(Environment::class)->get('base').ltrim($uri, '/');
        }

        return $uri;
    }
}
