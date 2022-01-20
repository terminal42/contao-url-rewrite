<?php

declare(strict_types=1);

namespace Terminal42\UrlRewriteBundle\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Terminal42\UrlRewriteBundle\DependencyInjection\Compiler\ConfigProviderPass;

class ConfigProviderPassTest extends TestCase
{
    /**
     * @var ConfigProviderPass
     */
    private $pass;

    protected function setUp(): void
    {
        $this->pass = new ConfigProviderPass('alias', 'chain', 'tag');
    }

    public function testInstantiation(): void
    {
        $this->assertInstanceOf(ConfigProviderPass::class, $this->pass);
    }

    public function testProcessSingleProvider(): void
    {
        $chainDefinition = new Definition();

        $providerDefinition = new Definition();
        $providerDefinition->addTag('tag', ['priority' => 32]);

        $container = new ContainerBuilder();
        $container->addDefinitions([
            'alias' => $chainDefinition,
            'chain' => $chainDefinition,
            'provider' => $providerDefinition,
        ]);

        $this->pass->process($container);

        $this->assertSame('provider', (string) $container->getAlias('alias'));
        $this->assertEmpty($chainDefinition->getMethodCalls());
    }

    public function testProcessMultipleProviders(): void
    {
        $chainDefinition = new Definition();

        $providerDefinition1 = new Definition();
        $providerDefinition1->addTag('tag', ['priority' => 32]);

        $providerDefinition2 = new Definition();
        $providerDefinition2->addTag('tag', ['priority' => 64]);

        $container = new ContainerBuilder();
        $container->addDefinitions([
            'alias' => $chainDefinition,
            'chain' => $chainDefinition,
            'provider1' => $providerDefinition1,
            'provider2' => $providerDefinition2,
        ]);

        $this->pass->process($container);

        $calls = $chainDefinition->getMethodCalls();

        $this->assertSame($chainDefinition, $container->getDefinition('alias'));
        $this->assertSame('addProvider', $calls[0][0]);
        $this->assertInstanceOf(Reference::class, $calls[0][1][0]);
        $this->assertSame('provider2', (string) $calls[0][1][0]);
        $this->assertSame('provider1', (string) $calls[1][1][0]);
    }

    public function testProcessMultipleProvidersNoChain(): void
    {
        $aliasDefinition = new Definition();

        $providerDefinition1 = new Definition();
        $providerDefinition1->addTag('tag', ['priority' => 32]);

        $providerDefinition2 = new Definition();
        $providerDefinition2->addTag('tag', ['priority' => 64]);

        $container = new ContainerBuilder();
        $container->addDefinitions([
            'alias' => $aliasDefinition,
            'provider1' => $providerDefinition1,
            'provider2' => $providerDefinition2,
        ]);

        $this->pass->process($container);

        $this->assertSame('provider2', (string) $container->getAlias('alias'));
    }
}
