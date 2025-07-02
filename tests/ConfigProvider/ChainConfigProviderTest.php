<?php

declare(strict_types=1);

namespace Terminal42\UrlRewriteBundle\Tests\ConfigProvider;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Terminal42\UrlRewriteBundle\ConfigProvider\ChainConfigProvider;
use Terminal42\UrlRewriteBundle\ConfigProvider\ConfigProviderInterface;
use Terminal42\UrlRewriteBundle\RewriteConfig;

class ChainConfigProviderTest extends TestCase
{
    public function testInstantiation(): void
    {
        $this->assertInstanceOf(ChainConfigProvider::class, new ChainConfigProvider());
    }

    public function testFind(): void
    {
        $config1 = new RewriteConfig('1', 'path/1');
        $config2 = new RewriteConfig('2', 'path/2');
        $config3 = new RewriteConfig('3', 'path/3');

        $chain = new ChainConfigProvider();
        $chain->addProvider($this->mockProvider([1 => $config1, 2 => $config2]));
        $chain->addProvider($this->mockProvider([3 => $config3]));

        $configs = $chain->findAll();

        $this->assertCount(3, $configs);
        $this->assertSame('path/1', $chain->find($configs[0]->getIdentifier())->getRequestPath());
        $this->assertSame('path/2', $chain->find($configs[1]->getIdentifier())->getRequestPath());
        $this->assertSame('path/3', $chain->find($configs[2]->getIdentifier())->getRequestPath());
        $this->assertNull($chain->find('bar.baz'));
    }

    /**
     * @return MockObject|ConfigProviderInterface
     */
    private function mockProvider(array $configs)
    {
        $provider = $this->createMock(ConfigProviderInterface::class);
        $provider
            ->method('find')
            ->willReturnCallback(static fn ($key) => $configs[$key] ?? null)
        ;

        $provider
            ->method('findAll')
            ->willReturn($configs)
        ;

        return $provider;
    }
}
