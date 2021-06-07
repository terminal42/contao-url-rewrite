<?php

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
use Terminal42\UrlRewriteBundle\ConfigProvider\DatabaseConfigProvider;

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
     * Generate the QR code and the URL.
     */
    public function generate(array $data, array $parameters = []): array
    {
        if (!isset($parameters['host'])) {
            throw new MissingMandatoryParametersException('The parameter "host" is mandatory');
        }

        $routeId = null;
        $rewriteId = DatabaseConfigProvider::class.':'.$data['id'];

        foreach ($this->router->getRouteCollection() as $id => $route) {
            // Skip the routes not matching the URL rewrite default
            if (!$route->hasDefault('_url_rewrite') || $route->getDefault('_url_rewrite') !== $rewriteId) {
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

        $renderer = new ImageRenderer(new RendererStyle(180, 0), new SvgImageBackEnd());
        $writer = new Writer($renderer);
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

        $url = $this->router->generate($routeId, $parameters, RouterInterface::ABSOLUTE_URL);

        return ['qrCode' => $writer->writeString($url), 'url' => $url];
    }
}
