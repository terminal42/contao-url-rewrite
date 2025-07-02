<?php

declare(strict_types=1);

namespace Terminal42\UrlRewriteBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ConfigProviderPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function __construct(
        private string $alias,
        private string $chain,
        private string $tag,
    ) {
    }

    public function process(ContainerBuilder $container): void
    {
        $services = $this->findAndSortTaggedServices($this->tag, $container);

        // If there's only one service or chain service is not present alias the first service
        if (1 === \count($services) || !$container->hasDefinition($this->chain)) {
            $container->setAlias($this->alias, (string) $services[0]);

            return;
        }

        $definition = $container->findDefinition($this->chain);

        // Add providers to the chain
        foreach ($services as $service) {
            $definition->addMethodCall('addProvider', [$service]);
        }
    }
}
