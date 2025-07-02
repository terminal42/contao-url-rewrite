<?php

declare(strict_types=1);

namespace Terminal42\UrlRewriteBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class Terminal42UrlRewriteExtension extends ConfigurableExtension
{
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('listener.yml');
        $loader->load('services.yml');

        $hasBackendManagement = (bool) $mergedConfig['backend_management'];

        // Set the "backend management" parameter
        $container->setParameter('terminal42_url_rewrite.backend_management', $hasBackendManagement);

        // Remove the database provider if backend management is not available
        if (!$hasBackendManagement) {
            $container->removeDefinition('terminal42_url_rewrite.provider.database');
        }

        // Set the entries as argument for bundle config provider
        if (isset($mergedConfig['entries']) && $container->hasDefinition('terminal42_url_rewrite.provider.bundle')) {
            $container->getDefinition('terminal42_url_rewrite.provider.bundle')->setArguments([$mergedConfig['entries']]);
        }
    }
}
