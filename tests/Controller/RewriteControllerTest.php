<?php

declare(strict_types=1);

namespace Terminal42\UrlRewriteBundle\Tests\Controller;

use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use PHPUnit\Framework\TestCase;
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
    public function testInstantiation()
    {
        $this->assertInstanceOf(RewriteController::class, new RewriteController(
            $this->mockConfigProvider(),
            $this->mockContaoFramework()
        ));
    }

    public function testIndexActionNoUrlRewriteAttribute()
    {
        $controller = new RewriteController($this->mockConfigProvider(), $this->mockContaoFramework());
        $request = $this->mockRequest(null);

        $this->expectException(RouteNotFoundException::class);
        $controller->indexAction($request);
    }

    public function testIndexActionNoUrlRewriteRecord()
    {
        $provider = $this->mockConfigProvider();
        $controller = new RewriteController($provider, $this->mockContaoFramework());
        $request = $this->mockRequest(1);

        $this->expectException(RouteNotFoundException::class);
        $controller->indexAction($request);
    }

    /**
     * @dataProvider indexActionRedirectDataProvider
     */
    public function testIndexActionRedirect($provided, $expected)
    {
        $provider = $this->mockConfigProvider($provided[0]);
        $controller = new RewriteController($provider, $this->mockContaoFramework());
        $request = $this->mockRequest(1, $provided[1], $provided[2]);
        $response = $controller->indexAction($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals($expected[0], $response->getTargetUrl());
        $this->assertEquals($expected[1], $response->getStatusCode());
    }

    public function indexActionRedirectDataProvider()
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
                    ['quux' => 'quuz']
                ],
                [
                    'http://domain.tld/page.html/foo/bar/quuz',
                    301
                ],
            ],
            'Absolute ' => [
                [
                    $config2,
                    ['baz' => 'bar'],
                    ['quux' => 'quuz']
                ],
                [
                    'http://domain.tld/foo/bar/quuz',
                    302
                ],
            ],
        ];
    }

    public function testIndexActionGone()
    {
        $provider = $this->mockConfigProvider(new RewriteConfig('1', 'foobar', 410));
        $controller = new RewriteController($provider, $this->mockContaoFramework());
        $request = $this->mockRequest(1);
        $response = $controller->indexAction($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(410, $response->getStatusCode());
        $this->assertEquals('Gone', $response->getContent());
    }

    public function testIndexActionInternalServerError()
    {
        $provider = $this->mockConfigProvider(new RewriteConfig('1', 'foobar'));
        $controller = new RewriteController($provider, $this->mockContaoFramework());
        $request = $this->mockRequest(1);
        $response = $controller->indexAction($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('Internal Server Error', $response->getContent());
    }

    public function testIndexActionServiceUnavailable()
    {
        $provider = $this->createMock(ConfigProviderInterface::class);

        $provider
            ->method('find')
            ->willThrowException(new TemporarilyUnavailableConfigProviderException())
        ;

        $controller = new RewriteController($provider, $this->mockContaoFramework());
        $request = $this->mockRequest(1);
        $response = $controller->indexAction($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(503, $response->getStatusCode());
        $this->assertEquals('Service Unavailable', $response->getContent());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ContaoFramework
     */
    private function mockContaoFramework()
    {
        $insertTags = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->addMethods(['replace'])
            ->getMock()
        ;

        $insertTags
            ->method('replace')
            ->willReturnCallback(function ($buffer) {
                return str_replace('{{link_url::1|absolute}}', 'http://domain.tld/page.html', $buffer);
            })
        ;

        $framework = $this->createMock(ContaoFramework::class);

        $framework
            ->method('createInstance')
            ->willReturn($insertTags)
        ;

        return $framework;
    }

    /**
     * @param mixed $urlRewrite
     * @param array $routeParams
     * @param array $query
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|Request
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
                $attributes
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
     * @param RewriteConfig|null $config
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ConfigProviderInterface
     */
    private function mockConfigProvider(RewriteConfig $config = null)
    {
        $provider = $this->createMock(ConfigProviderInterface::class);

        $provider
            ->method('find')
            ->willReturn($config)
        ;

        return $provider;
    }
}
