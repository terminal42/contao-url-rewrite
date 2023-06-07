<?php

declare(strict_types=1);

namespace Terminal42\UrlRewriteBundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;
use Terminal42\UrlRewriteBundle\ConfigProvider\ChainConfigProvider;
use Terminal42\UrlRewriteBundle\ConfigProvider\DatabaseConfigProvider;
use Terminal42\UrlRewriteBundle\QrCodeGenerator;
use Terminal42\UrlRewriteBundle\Routing\UrlRewriteLoader;

class QrCodeGeneratorTest extends TestCase
{
    /**
     * @var QrCodeGenerator
     */
    private $qrCodeGenerator;

    /**
     * @var RouterInterface
     */
    private $router;

    protected function setUp(): void
    {
        $this->router = $this->createMock(Router::class);
        $this->qrCodeGenerator = new QrCodeGenerator($this->router);
    }

    public function testInstantiation(): void
    {
        $this->assertInstanceOf(QrCodeGenerator::class, $this->qrCodeGenerator);
    }

    public function testValidate(): void
    {
        $this->assertTrue($this->qrCodeGenerator->validate(['requestPath' => 'foo/bar', 'inactive' => false]));

        $this->assertFalse($this->qrCodeGenerator->validate(['requestPath' => 'foo/bar', 'inactive' => true]));
        $this->assertFalse($this->qrCodeGenerator->validate(['requestPath' => '', 'inactive' => true]));
        $this->assertFalse($this->qrCodeGenerator->validate(['requestPath' => '', 'inactive' => false]));
    }

    public function testGenerateImage(): void
    {
        $image = $this->qrCodeGenerator->generateImage('https://domain.tld/foo/bar?test=1');

        $this->assertSame(file_get_contents(__DIR__.'/Fixtures/qr-code.svg'), $image);
    }

    public function testGenerateUrl(): void
    {
        $routeIncorrect = new Route('foo/baz');

        $routeCorrect1 = new Route('foo/bar');
        $routeCorrect1->setHost('domain.tld');
        $routeCorrect1->setDefault(UrlRewriteLoader::ATTRIBUTE_NAME, ChainConfigProvider::getConfigIdentifier(DatabaseConfigProvider::class, '123'));

        $routeCorrect2 = new Route('foo/bar');
        $routeCorrect2->setDefault(UrlRewriteLoader::ATTRIBUTE_NAME, ChainConfigProvider::getConfigIdentifier(DatabaseConfigProvider::class, '456'));

        $this->router
            ->method('getRouteCollection')
            ->willReturn([666 => $routeIncorrect, 123 => $routeCorrect1, 456 => $routeCorrect2])
        ;
        $this->router
            ->method('getContext')
            ->willReturn(new RequestContext())
        ;
        $this->router
            ->method('generate')
            ->willReturn('https://domain.tld/foo/bar')
        ;

        $this->assertSame('https://domain.tld/foo/bar', $this->qrCodeGenerator->generateUrl(['id' => 123], ['host' => 'domain.tld', 'scheme' => 'https']));
        $this->assertSame('https://domain.tld/foo/bar', $this->qrCodeGenerator->generateUrl(['id' => 456], ['host' => 'domain.tld']));
    }

    public function testGenerateUrlMissingMandatoryParametersException(): void
    {
        $this->expectException(MissingMandatoryParametersException::class);
        $this->expectExceptionMessage('The parameter "host" is mandatory');

        $this->qrCodeGenerator->generateUrl([]);
    }
}
