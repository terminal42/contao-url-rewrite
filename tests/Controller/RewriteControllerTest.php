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

        $request = $this->createRequest(null);

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
        $request = $this->createRequest(1);

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
        $request = $this->createRequest(1, $provided[1], $provided[2]);
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
                    ['responseUri' => '{{link_url::{bar}|absolute}}/foo///{baz}/{quux}', 'responseCode' => 301],
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
                    ['responseUri' => 'foo///{baz}/{quux}', 'responseCode' => 302],
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
        $db = $this->createMock(Connection::class);

        $db
            ->method('fetchAssoc')
            ->willReturn([
                'responseCode' => 410,
            ])
        ;

        $controller = new RewriteController($db, $this->createFrameworkMock());
        $request = $this->createRequest(1);
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

        $request = $this->createRequest(1);
        $response = $controller->indexAction($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('Internal Server Error', $response->getContent());
    }

    private function createFrameworkMock()
    {
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
            ->method('createInstance')
            ->willReturn($insertTags)
        ;

        return $framework;
    }

    private function createRequest($urlRewrite, $routeParams = [], $query = [])
    {
        $request = $this
            ->getMockBuilder(Request::class)
            ->setConstructorArgs([
                $query,
                [],
                [
                    '_url_rewrite' => $urlRewrite,
                    '_route_params' => $routeParams,
                ]
            ])
            ->setMethods(['getSchemeAndHttpHost', 'getBasePath'])
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
}
