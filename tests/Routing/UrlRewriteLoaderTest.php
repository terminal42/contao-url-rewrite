<?php

namespace Terminal42\UrlRewriteBundle\Tests\ContaoManager;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Terminal42\UrlRewriteBundle\Routing\UrlRewriteLoader;

class UrlRewriteLoaderTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(UrlRewriteLoader::class, new UrlRewriteLoader($this->createMock(Connection::class)));
    }

    public function testSupports()
    {
        $loader = new UrlRewriteLoader($this->createMock(Connection::class));

        $this->assertTrue($loader->supports('', 'terminal42_url_rewrite'));
        $this->assertFalse($loader->supports('', 'foobar'));
        $this->assertFalse($loader->supports(''));
    }

    public function testLoadedTwice()
    {
        $this->expectException(\RuntimeException::class);

        $loader = new UrlRewriteLoader($this->createMock(Connection::class));
        $loader->load('');
        $loader->load('');
    }

    public function testLoadNoDatabaseConnection()
    {
        $db = $this->createMock(Connection::class);

        $db
            ->method('isConnected')
            ->willReturn(null)
        ;

        $loader = new UrlRewriteLoader($db);

        $this->assertNull(null, $loader->load(''));
    }

    public function testLoadNoDatabaseRecords()
    {
        $db = $this->createMock(Connection::class);

        $db
            ->method('isConnected')
            ->willReturn(true)
        ;

        $db
            ->method('fetchAll')
            ->willReturn([])
        ;

        $loader = new UrlRewriteLoader($db);

        $this->assertNull(null, $loader->load(''));
    }

    /**
     * @dataProvider getRouteCollectionProvider
     */
    public function testLoad($provided, $expected)
    {
        $db = $this->createMock(Connection::class);

        $db
            ->method('isConnected')
            ->willReturn(true)
        ;

        $db
            ->method('fetchAll')
            ->willReturn([$provided])
        ;

        $loader = new UrlRewriteLoader($db);
        $collection = $loader->load('');
        $routes = $collection->getIterator();

        $this->assertInstanceOf(RouteCollection::class, $collection);
        $this->assertCount(count($expected), $routes);

        $index = 0;

        /** @var Route $route */
        foreach ($routes as $route) {
            $this->assertContains('GET', $route->getMethods());
            $this->assertEquals('terminal42_url_rewrite.rewrite_controller:indexAction', $route->getDefault('_controller'));
            $this->assertArrayHasKey('_url_rewrite', $route->getDefaults());
            $this->assertEquals($expected[$index]['path'], $route->getPath());
            $this->assertEquals($expected[$index]['scheme'], $route->getSchemes());
            $this->assertEquals($expected[$index]['requirements'], $route->getRequirements());
            $this->assertEquals($expected[$index]['host'], $route->getHost());

            $index++;
        }
    }

    public function getRouteCollectionProvider()
    {
        return [
            'Single route' => [
                [
                    'id' => 1,
                    'requestPath' => 'foo/bar'
                ],
                [
                    [
                        'path' => '/foo/bar',
                        'scheme' => [],
                        'requirements' => [],
                        'host' => '',
                    ],
                ],
            ],

            'Multiple hosts' => [
                [
                    'id' => 2,
                    'requestPath' => 'foo/baz',
                    'requestHosts' => ['domain1.tld', 'domain2.tld'],
                    'requestScheme' => 'http',
                    'requestRequirements' => ['foo: \d+', 'baz: \s+'],
                ],
                [
                    [
                        'path' => '/foo/baz',
                        'scheme' => ['http'],
                        'requirements' => ['foo' => '\d+', 'baz' => '\s+'],
                        'host' => 'domain1.tld',
                    ],
                    [
                        'path' => '/foo/baz',
                        'scheme' => ['http'],
                        'requirements' => ['foo' => '\d+', 'baz' => '\s+'],
                        'host' => 'domain2.tld',
                    ],
                ]
            ],

            'Invalid #1' => [
                ['id' => 3],
                []
            ],

            'Invalid #2' => [
                ['requestPath' => 'invalid'],
                []
            ],

            'Invalid #3' => [
                [],
                []
            ],
        ];
    }
}
