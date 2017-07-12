<?php

namespace Terminal42\UrlRewriteBundle\Test\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Terminal42\UrlRewriteBundle\ContaoManager\Plugin;
use Terminal42\UrlRewriteBundle\Terminal42UrlRewriteBundle;

class PluginTest extends TestCase
{
    public function testInstantiation()
    {
        $plugin = new Plugin();

        $this->assertInstanceOf(Plugin::class, $plugin);
        $this->assertInstanceOf(BundlePluginInterface::class, $plugin);
        $this->assertInstanceOf(RoutingPluginInterface::class, $plugin);
    }

    public function testGetBundles()
    {
        $plugin = new Plugin();
        $bundles = $plugin->getBundles($this->createMock(ParserInterface::class));

        /** @var BundleConfig $config */
        $config = $bundles[0];

        $this->assertCount(1, $bundles);
        $this->assertInstanceOf(BundleConfig::class, $config);
        $this->assertEquals(Terminal42UrlRewriteBundle::class, $config->getName());
        $this->assertEquals([ContaoCoreBundle::class], $config->getLoadAfter());
    }

    public function testGetRouteCollectionNoDatabaseConnection()
    {
        $kernel = $this->createKernelMock();

        $kernel
            ->getContainer()
            ->get('database_connection')
            ->method('isConnected')
            ->willReturn(null)
        ;

        $plugin = new Plugin();

        $this->assertNull(null, $plugin->getRouteCollection($this->createMock(LoaderResolver::class), $kernel));
    }

    public function testGetRouteCollectionNoDatabaseRecords()
    {
        $kernel = $this->createKernelMock();
        $db = $kernel->getContainer()->get('database_connection');

        $db
            ->method('isConnected')
            ->willReturn(true)
        ;

        $db
            ->method('fetchAll')
            ->willReturn([])
        ;

        $plugin = new Plugin();

        $this->assertNull(null, $plugin->getRouteCollection($this->createMock(LoaderResolver::class), $kernel));
    }

    /**
     * @dataProvider getRouteCollectionProvider
     */
    public function testGetRouteCollection($provided, $expected)
    {
        $kernel = $this->createKernelMock();
        $db = $kernel->getContainer()->get('database_connection');

        $db
            ->method('isConnected')
            ->willReturn(true)
        ;

        $db
            ->method('fetchAll')
            ->willReturn([$provided])
        ;

        $plugin = new Plugin();
        $collection = $plugin->getRouteCollection($this->createMock(LoaderResolver::class), $kernel);
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
            // Single route
            [
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

            // Multiple hosts
            [
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

            // Invalid #1
            [
                ['id' => 3],
                []
            ],

            // Invalid #2
            [
                ['requestPath' => 'invalid'],
                []
            ],

            // Invalid #3
            [
                [],
                []
            ],
        ];
    }

    private function createKernelMock()
    {
        $db = $this->createMock(Connection::class);

        $container = new Container();
        $container->set('database_connection', $db);

        $kernel = $this->createMock(Kernel::class);
        $kernel
            ->method('getContainer')
            ->willReturn($container)
        ;

        return $kernel;
    }
}
