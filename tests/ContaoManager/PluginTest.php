<?php

namespace Terminal42\UrlRewriteBundle\Tests\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\YamlFileLoader;
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

    public function testGetRouteCollection()
    {
        $loader = $this->createMock(YamlFileLoader::class);

        $loader
            ->method('load')
            ->willReturn(new RouteCollection())
        ;

        $resolver = $this->createMock(LoaderResolver::class);

        $resolver
            ->method('resolve')
            ->willReturn($loader)
        ;

        $plugin = new Plugin();

        $this->assertInstanceOf(RouteCollection::class, $plugin->getRouteCollection($resolver, $this->createMock(Kernel::class)));
    }

    public function testGetRouteCollectionNull()
    {
        $resolver = $this->createMock(LoaderResolver::class);

        $resolver
            ->method('resolve')
            ->willReturn(null)
        ;

        $plugin = new Plugin();

        $this->assertNull($plugin->getRouteCollection($resolver, $this->createMock(Kernel::class)));
    }
}
