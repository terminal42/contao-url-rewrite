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

        static::assertInstanceOf(Plugin::class, $plugin);
        static::assertInstanceOf(BundlePluginInterface::class, $plugin);
        static::assertInstanceOf(RoutingPluginInterface::class, $plugin);
    }

    public function testGetBundles()
    {
        $plugin = new Plugin();
        $bundles = $plugin->getBundles($this->createMock(ParserInterface::class));

        /** @var BundleConfig $config */
        $config = $bundles[0];

        static::assertCount(1, $bundles);
        static::assertInstanceOf(BundleConfig::class, $config);
        static::assertEquals(Terminal42UrlRewriteBundle::class, $config->getName());
        static::assertEquals([ContaoCoreBundle::class], $config->getLoadAfter());
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

        static::assertNull(null, $plugin->getRouteCollection(static::createMock(LoaderResolver::class), $kernel));
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

        static::assertNull(null, $plugin->getRouteCollection(static::createMock(LoaderResolver::class), $kernel));
    }

    public function testGetRouteCollection()
    {
        $kernel = $this->createKernelMock();
        $db = $kernel->getContainer()->get('database_connection');

        $db
            ->method('isConnected')
            ->willReturn(true)
        ;

        $db
            ->method('fetchAll')
            ->willReturn([
                [
                    'id' => 1,
                    'requestPath' => 'foo/bar',
                ],
                [
                    'id' => 2,
                    'requestPath' => 'foo/baz',
                    'requestHosts' => ['domain1.tld', 'domain2.tld'],
                    'requestScheme' => 'http',
                    'requestRequirements' => ['foo: \d+', 'baz: \s+'],
                ],
                [
                    'id' => 3,
                ],
                [
                    'requestPath' => 'invalid',
                ],
                [
                    // empty
                ],
            ])
        ;

        $plugin = new Plugin();
        $collection = $plugin->getRouteCollection(static::createMock(LoaderResolver::class), $kernel);
        $routes = $collection->getIterator();

        static::assertInstanceOf(RouteCollection::class, $collection);
        static::assertCount(3, $routes);

        /** @var Route $route */
        foreach ($routes as $key => $route) {
            static::assertContains('GET', $route->getMethods());
            static::assertEquals('terminal42_url_rewrite.rewrite_controller:indexAction', $route->getDefault('_controller'));
            static::assertArrayHasKey('_url_rewrite', $route->getDefaults());

            switch ($key) {
                case 'url_rewrite_0':
                    static::assertEquals('/foo/bar', $route->getPath());
                    break;
                case 'url_rewrite_1':
                case 'url_rewrite_2':
                    static::assertEquals('/foo/baz', $route->getPath());
                    static::assertContains('http', $route->getSchemes());
                    static::assertEquals(['foo' => '\d+', 'baz' => '\s+'], $route->getRequirements());

                    if ($key === 'url_rewrite_1') {
                        static::assertEquals('domain1.tld', $route->getHost());
                    } else {
                        static::assertEquals('domain2.tld', $route->getHost());
                    }
                    break;
            }
        }
    }

    private function createKernelMock()
    {
        $db = static::createMock(Connection::class);

        $container = new Container();
        $container->set('database_connection', $db);

        $kernel = static::createMock(Kernel::class);
        $kernel
            ->method('getContainer')
            ->willReturn($container)
        ;

        return $kernel;
    }
}
