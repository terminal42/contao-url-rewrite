<?php

declare(strict_types=1);

namespace Terminal42\UrlRewriteBundle\Tests\ContaoManager;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Terminal42\UrlRewriteBundle\ConfigProvider\ConfigProviderInterface;
use Terminal42\UrlRewriteBundle\RewriteConfig;
use Terminal42\UrlRewriteBundle\Routing\UrlRewriteLoader;

class UrlRewriteLoaderTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(UrlRewriteLoader::class, new UrlRewriteLoader($this->mockConfigProvider()));
    }

    public function testSupports()
    {
        $loader = new UrlRewriteLoader($this->mockConfigProvider());

        $this->assertTrue($loader->supports('', 'terminal42_url_rewrite'));
        $this->assertFalse($loader->supports('', 'foobar'));
        $this->assertFalse($loader->supports(''));
    }

    public function testLoadedTwice()
    {
        $this->expectException(\RuntimeException::class);

        $loader = new UrlRewriteLoader($this->mockConfigProvider());
        $loader->load('');
        $loader->load('');
    }

    public function testNoRoutes()
    {
        $loader = new UrlRewriteLoader($this->mockConfigProvider());
        $collection = $loader->load('');

        $this->assertInstanceOf(RouteCollection::class, $collection);
        $this->assertCount(0, $collection->getIterator());
    }

    /**
     * @dataProvider getRouteCollectionProvider
     */
    public function testLoad($provided, $expected)
    {
        $provider = $this->mockConfigProvider([$provided]);
        $loader = new UrlRewriteLoader($provider);
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
        $config1 = new RewriteConfig('1', 'foo/bar');

        $config2 = new RewriteConfig('2', 'foo/baz');
        $config2->setRequestHosts(['domain1.tld', 'domain2.tld']);
        $config2->setRequestRequirements(['foo' => '\d+', 'baz' => '\s+']);

        $config3 = new RewriteConfig('3', 'foo/bar');
        $config3->setRequestCondition('context.getMethod() in [\'GET\']');

        $config4 = new RewriteConfig('4', 'foo/baz');
        $config4->setRequestHosts(['domain1.tld', 'domain2.tld']);
        $config4->setRequestCondition('context.getMethod() in [\'GET\']');

        return [
            'Basic – single route' => [
                $config1,
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
                $config2,
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
                $config3,
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
                $config4,
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

            'Invalid' => [
                new RewriteConfig('1', ''),
                []
            ],
        ];
    }

    /**
     * @param array $configs
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ConfigProviderInterface
     */
    private function mockConfigProvider(array $configs = [])
    {
        $provider = $this->createMock(ConfigProviderInterface::class);

        $provider
            ->method('findAll')
            ->willReturn($configs)
        ;

        return $provider;
    }
}
