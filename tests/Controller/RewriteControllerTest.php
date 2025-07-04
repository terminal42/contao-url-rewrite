<?php

declare(strict_types=1);

namespace Terminal42\UrlRewriteBundle\Tests\Controller;

use Contao\CoreBundle\InsertTag\InsertTagParser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Terminal42\UrlRewriteBundle\ConfigProvider\ConfigProviderInterface;
use Terminal42\UrlRewriteBundle\Controller\RewriteController;
use Terminal42\UrlRewriteBundle\Exception\TemporarilyUnavailableConfigProviderException;
use Terminal42\UrlRewriteBundle\RewriteConfig;

class RewriteControllerTest extends TestCase
{
    public function testIndexActionNoUrlRewriteAttribute(): void
    {
        $controller = new RewriteController($this->mockConfigProvider(), $this->mockInsertTagParser(), $this->createMock(ExpressionLanguage::class));
        $request = $this->mockRequest(null);

        $this->expectException(RouteNotFoundException::class);
        $controller->indexAction($request);
    }

    public function testIndexActionNoUrlRewriteRecord(): void
    {
        $provider = $this->mockConfigProvider();
        $controller = new RewriteController($provider, $this->mockInsertTagParser(), $this->createMock(ExpressionLanguage::class));
        $request = $this->mockRequest(1);

        $this->expectException(RouteNotFoundException::class);
        $controller->indexAction($request);
    }

    /**
     * @dataProvider indexActionRedirectDataProvider
     */
    public function testIndexActionRedirect($provided, $expected): void
    {
        $provider = $this->mockConfigProvider($provided[0]);
        $controller = new RewriteController($provider, $this->mockInsertTagParser(), $this->createMock(ExpressionLanguage::class));
        $request = $this->mockRequest(1, $provided[1], $provided[2]);
        $response = $controller->indexAction($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($expected[0], $response->getTargetUrl());
        $this->assertSame($expected[1], $response->getStatusCode());
    }

    public static function indexActionRedirectDataProvider(): iterable
    {
        $config1 = new RewriteConfig('1', 'foobar');
        $config1->setResponseUri('{{link_url::{bar}|absolute}}/foo///{baz}/{quux}');

        $config2 = new RewriteConfig('2', 'foobar', 302);
        $config2->setResponseUri('foo///{baz}/{quux}');

        return [
            'Insert tags' => [
                [
                    $config1,
                    ['bar' => 1, 'baz' => 'bar'],
                    ['quux' => 'quuz'],
                ],
                [
                    'http://domain.tld/page.html/foo/bar/quuz',
                    301,
                ],
            ],
            'Absolute ' => [
                [
                    $config2,
                    ['baz' => 'bar'],
                    ['quux' => 'quuz'],
                ],
                [
                    'http://domain.tld/foo/bar/quuz',
                    302,
                ],
            ],
        ];
    }

    public function testIndexActionGone(): void
    {
        $provider = $this->mockConfigProvider(new RewriteConfig('1', 'foobar', 410));
        $controller = new RewriteController($provider, $this->mockInsertTagParser(), $this->createMock(ExpressionLanguage::class));
        $request = $this->mockRequest(1);
        $response = $controller->indexAction($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(410, $response->getStatusCode());
        $this->assertSame('Gone', $response->getContent());
    }

    public function testIndexActionInternalServerError(): void
    {
        $provider = $this->mockConfigProvider(new RewriteConfig('1', 'foobar'));
        $controller = new RewriteController($provider, $this->mockInsertTagParser(), $this->createMock(ExpressionLanguage::class));
        $request = $this->mockRequest(1);
        $response = $controller->indexAction($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('Internal Server Error', $response->getContent());
    }

    public function testIndexActionServiceUnavailable(): void
    {
        $provider = $this->createMock(ConfigProviderInterface::class);
        $provider
            ->method('find')
            ->willThrowException(new TemporarilyUnavailableConfigProviderException())
        ;

        $controller = new RewriteController($provider, $this->mockInsertTagParser(), $this->createMock(ExpressionLanguage::class));
        $request = $this->mockRequest(1);
        $response = $controller->indexAction($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame('Service Unavailable', $response->getContent());
    }

    /**
     * @param array $routeParams
     * @param array $query
     *
     * @return MockObject|Request
     */
    private function mockRequest($urlRewrite = null, $routeParams = [], $query = [])
    {
        $attributes = ['_route_params' => $routeParams];

        if (null !== $urlRewrite) {
            $attributes['_url_rewrite'] = $urlRewrite;
        }

        $request = $this
            ->getMockBuilder(Request::class)
            ->setConstructorArgs([
                $query,
                [],
                $attributes,
            ])
            ->onlyMethods(['getSchemeAndHttpHost', 'getBasePath'])
            ->getMock()
        ;

        $request
            ->method('getSchemeAndHttpHost')
            ->willReturn('http://domain.tld')
        ;

        $request
            ->method('getBasePath')
            ->willReturn('')
        ;

        return $request;
    }

    /**
     * @return MockObject|ConfigProviderInterface
     */
    private function mockConfigProvider(RewriteConfig|null $config = null)
    {
        $provider = $this->createMock(ConfigProviderInterface::class);
        $provider
            ->method('find')
            ->willReturn($config)
        ;

        return $provider;
    }

    /**
     * @return MockObject|InsertTagParser
     */
    private function mockInsertTagParser()
    {
        $provider = $this->createMock(InsertTagParser::class);
        $provider
            ->method('replaceInline')
            ->willReturnCallback(static fn ($buffer) => str_replace('{{link_url::1|absolute}}', 'http://domain.tld/page.html', $buffer))
        ;

        return $provider;
    }
}
