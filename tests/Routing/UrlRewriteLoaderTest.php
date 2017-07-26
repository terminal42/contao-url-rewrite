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

    public function testLoadDatabaseCaughtException()
    {
        $db = $this->createMock(Connection::class);

        $db
            ->method('fetchAll')
            ->willThrowException(new \PDOException())
        ;

        $loader = new UrlRewriteLoader($db);
        $collection = $loader->load('');

        $this->assertInstanceOf(RouteCollection::class, $collection);
        $this->assertCount(0, $collection->getIterator());
    }

    public function testLoadDatabaseUncaughtException()
    {
        $this->expectException(\RuntimeException::class);

        $db = $this->createMock(Connection::class);

        $db
            ->method('fetchAll')
            ->willThrowException(new \RuntimeException())
        ;

        $loader = new UrlRewriteLoader($db);
        $loader->load('');
    }

    public function testLoadNoDatabaseRecords()
    {
        $db = $this->createMock(Connection::class);

        $db
            ->method('fetchAll')
            ->willReturn([])
        ;

        $loader = new UrlRewriteLoader($db);
        $collection = $loader->load('');

        $this->assertInstanceOf(RouteCollection::class, $collection);
        $this->assertCount(0, $collection->getIterator());
    }

    public function testLoadUnsupportedConfigType()
    {
        $this->expectException(\InvalidArgumentException::class);

        $db = $this->createMock(Connection::class);

        $db
            ->method('fetchAll')
            ->willReturn([
                ['id' => 1, 'type' => 'foobar', 'requestPath' => 'foobar']
            ])
        ;

        $loader = new UrlRewriteLoader($db);
        $loader->load('');
    }

    /**
     * @dataProvider getRouteCollectionProvider
     */
    public function testLoad($provided, $expected)
    {
        $db = $this->createMock(Connection::class);

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
            $this->assertEquals('terminal42_url_rewrite.rewrite_controller:indexAction', $route->getDefault('_controller'));
            $this->assertArrayHasKey('_url_rewrite', $route->getDefaults());
            $this->assertEquals($expected[$index]['methods'], $route->getMethods());
            $this->assertEquals($expected[$index]['path'], $route->getPath());
            $this->assertEquals($expected[$index]['requirements'], $route->getRequirements());
            $this->assertEquals($expected[$index]['host'], $route->getHost());
            $this->assertEquals($expected[$index]['condition'], $route->getCondition());

            $index++;
        }
    }

    public function getRouteCollectionProvider()
    {
        return [
            'Basic – single route' => [
                [
                    'id' => 1,
                    'type' => 'basic',
                    'requestPath' => 'foo/bar'
                ],
                [
                    [
                        'path' => '/foo/bar',
                        'methods' => ['GET'],
                        'requirements' => [],
                        'host' => '',
                        'condition' => '',
                    ],
                ],
            ],

            'Basic – multiple hosts' => [
                [
                    'id' => 1,
                    'type' => 'basic',
                    'requestPath' => 'foo/baz',
                    'requestHosts' => ['domain1.tld', 'domain2.tld'],
                    'requestRequirements' => [
                        ['key' => 'foo', 'value' => '\d+'],
                        ['key' => 'baz', 'value' => '\s+']
                    ],
                ],
                [
                    [
                        'path' => '/foo/baz',
                        'methods' => ['GET'],
                        'requirements' => ['foo' => '\d+', 'baz' => '\s+'],
                        'host' => 'domain1.tld',
                        'condition' => '',
                    ],
                    [
                        'path' => '/foo/baz',
                        'methods' => ['GET'],
                        'requirements' => ['foo' => '\d+', 'baz' => '\s+'],
                        'host' => 'domain2.tld',
                        'condition' => '',
                    ],
                ]
            ],

            'Expert – single route' => [
                [
                    'id' => 1,
                    'type' => 'expert',
                    'requestPath' => 'foo/bar',
                    'requestCondition' => 'context.getMethod() in [\'GET\']',
                ],
                [
                    [
                        'path' => '/foo/bar',
                        'methods' => [],
                        'requirements' => [],
                        'host' => '',
                        'condition' => 'context.getMethod() in [\'GET\']',
                    ],
                ],
            ],

            'Expert – multiple hosts' => [
                [
                    'id' => 1,
                    'type' => 'expert',
                    'requestPath' => 'foo/baz',
                    'requestHosts' => ['domain1.tld', 'domain2.tld'],
                    'requestCondition' => 'context.getMethod() in [\'GET\']',
                ],
                [
                    [
                        'path' => '/foo/baz',
                        'methods' => [],
                        'requirements' => [],
                        'host' => 'domain1.tld',
                        'condition' => 'context.getMethod() in [\'GET\']',
                    ],
                    [
                        'path' => '/foo/baz',
                        'methods' => [],
                        'requirements' => [],
                        'host' => 'domain2.tld',
                        'condition' => 'context.getMethod() in [\'GET\']',
                    ],
                ]
            ],

            'Invalid #1' => [
                ['id' => 1],
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
