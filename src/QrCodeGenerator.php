<?php

declare(strict_types=1);

namespace Terminal42\UrlRewriteBundle;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\RouterInterface;
use Terminal42\UrlRewriteBundle\ConfigProvider\ChainConfigProvider;
use Terminal42\UrlRewriteBundle\ConfigProvider\DatabaseConfigProvider;

class QrCodeGenerator
{
    public function __construct(private readonly RouterInterface $router)
    {
    }

    /**
     * Return true if QR code can be generated from given rewrite record.
     */
    public function validate(array $data): bool
    {
        return '' !== $data['requestPath'] && !$data['inactive'];
    }

    public function generateImage(string $url): string
    {
        $renderer = new ImageRenderer(new RendererStyle(180, 0), new SvgImageBackEnd());
        $writer = new Writer($renderer);

        return $writer->writeString($url);
    }

    public function generateUrl(array $data, array $parameters = []): string
    {
        if (!isset($parameters['host'])) {
            throw new MissingMandatoryParametersException('The parameter "host" is mandatory');
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
            $context->setParameter('hosts', $parameters['host']);
            unset($parameters['host']);
        }

        $routeId = ChainConfigProvider::getConfigIdentifier(DatabaseConfigProvider::class, (string) $data['id']);

        return $this->router->generate($routeId, $parameters, RouterInterface::ABSOLUTE_URL);
    }
}
