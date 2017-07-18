<?php

namespace Terminal42\UrlRewriteBundle\Tests\Controller;

use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Terminal42\UrlRewriteBundle\Controller\RewriteController;

class RewriteControllerTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(RewriteController::class, new RewriteController(
            $this->createMock(Connection::class),
            $this->createMock(ContaoFramework::class)
        ));
    }

    public function testIndexActionNoUrlRewriteAttribute()
    {
        $controller = new RewriteController(
            $this->createMock(Connection::class),
            $this->createMock(ContaoFramework::class)
        );

        $request = new Request();
        $request->attributes->set('_url_rewrite', null);

        $this->expectException(RouteNotFoundException::class);
        $controller->indexAction($request);
    }

    public function testIndexActionNoUrlRewriteRecord()
    {
        $db = $this->createMock(Connection::class);

        $db
            ->method('fetchAssoc')
            ->willReturn(false)
        ;

        $controller = new RewriteController($db, $this->createMock(ContaoFramework::class));

        $request = new Request();
        $request->attributes->set('_url_rewrite', 1);

        $this->expectException(RouteNotFoundException::class);
        $controller->indexAction($request);
    }

    /**
     * @dataProvider indexActionRedirectDataProvider
     */
    public function testIndexActionRedirect($provided, $expected)
    {
        $db = $this->createMock(Connection::class);

        $db
            ->method('fetchAssoc')
            ->willReturn($provided[0])
        ;

        $controller = new RewriteController($db, $this->createFrameworkMock());

        $request = new Request();
        $request->attributes->set('_url_rewrite', 1);
        $request->attributes->set('_route_params', $provided[1]);

        $response = $controller->indexAction($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals($expected[0], $response->getTargetUrl());
        $this->assertEquals($expected[1], $response->getStatusCode());
    }

    public function indexActionRedirectDataProvider()
    {
        return [
            'Insert tags' => [
                [
                    ['responseUri' => '{{link_url::{bar}|absolute}}/foo///{baz}', 'responseCode' => 301],
                    ['bar' => 1, 'baz' => 'bar'],
                ],
                [
                    'http://domain.tld/page.html/foo/bar',
                    301
                ],
            ],
            'Absolute ' => [
                [
                    ['responseUri' => 'foo///{baz}', 'responseCode' => 302],
                    ['baz' => 'bar'],
                ],
                [
                    'http://domain.tld/foo/bar',
                    302
                ],
            ],
        ];
    }

    public function testIndexActionGone()
    {
        $db = $this->createMock(Connection::class);

        $db
            ->method('fetchAssoc')
            ->willReturn([
                'responseCode' => 410,
            ])
        ;

        $controller = new RewriteController($db, $this->createFrameworkMock());

        $request = new Request();
        $request->attributes->set('_url_rewrite', 1);

        $response = $controller->indexAction($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(410, $response->getStatusCode());
        $this->assertEquals('Gone', $response->getContent());
    }

    public function testIndexActionInternalServerError()
    {
        $db = $this->createMock(Connection::class);

        $db
            ->method('fetchAssoc')
            ->willReturn(['responseCode' => 301])
        ;

        $controller = new RewriteController($db, $this->createFrameworkMock());

        $request = new Request();
        $request->attributes->set('_url_rewrite', 1);

        $response = $controller->indexAction($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('Internal Server Error', $response->getContent());
    }

    private function createFrameworkMock()
    {
        $environment = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock()
        ;

        $environment
            ->method('get')
            ->willReturn('http://domain.tld/')
        ;

        $insertTags = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['replace'])
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
            ->method('getAdapter')
            ->willReturn($environment)
        ;

        $framework
            ->method('createInstance')
            ->willReturn($insertTags)
        ;

        return $framework;
    }
}
