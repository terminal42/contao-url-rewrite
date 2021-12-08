<?php

declare(strict_types=1);

/*
 * UrlRewrite Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2021, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

namespace Terminal42\UrlRewriteBundle;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\RouterInterface;
use Terminal42\UrlRewriteBundle\ConfigProvider\ChainConfigProvider;
use Terminal42\UrlRewriteBundle\ConfigProvider\DatabaseConfigProvider;
use Terminal42\UrlRewriteBundle\Routing\UrlRewriteLoader;

class QrCodeGenerator
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * QrCodeGenerator constructor.
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * Return true if QR code can be generated from given rewrite record.
     */
    public function validate(array $data): bool
    {
        return '' !== $data['requestPath'] && !$data['inactive'];
    }

    /**
     * Generate the image.
     */
    public function generateImage(string $url): string
    {
        $renderer = new ImageRenderer(new RendererStyle(180, 0), new SvgImageBackEnd());
        $writer = new Writer($renderer);

        return $writer->writeString($url);
    }

    /**
     * Generate the URL.
     */
    public function generateUrl(array $data, array $parameters = []): string
    {
        if (!isset($parameters['host'])) {
            throw new MissingMandatoryParametersException('The parameter "host" is mandatory');
        }

        $routeId = null;
        $rewriteId = ChainConfigProvider::getConfigIdentifier(DatabaseConfigProvider::class, (string) $data['id']);

        foreach ($this->router->getRouteCollection() as $id => $route) {
            // Skip the routes not matching the URL rewrite default
            if (!$route->hasDefault(UrlRewriteLoader::ATTRIBUTE_NAME) || $route->getDefault(UrlRewriteLoader::ATTRIBUTE_NAME) !== $rewriteId) {
                continue;
            }

            $routeHost = $route->getHost();

            // Match the route host
            if ('' === $routeHost || $parameters['host'] === $routeHost) {
                $routeId = $id;

                // Unset the host from parameters, if it's already in the route settings
                if ('' !== $routeHost) {
                    unset($parameters['host']);
                }

                break;
            }
        }

        if (null === $routeId) {
            throw new \RuntimeException(sprintf('Unable to determine route ID for rewrite ID %s', $data['id']));
        }

        $context = $this->router->getContext();

        // Set the scheme
        if (isset($parameters['scheme'])) {
            $context->setScheme($parameters['scheme']);
            unset($parameters['scheme']);
        }

        // Override the route host, if it's a route without restriction
        if (isset($parameters['host'])) {
            $context->setHost($parameters['host']);
            unset($parameters['host']);
        }

        return $this->router->generate((string) $routeId, $parameters, RouterInterface::ABSOLUTE_URL);
    }
}
