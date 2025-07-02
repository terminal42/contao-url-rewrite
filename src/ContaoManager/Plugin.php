<?php

declare(strict_types=1);

namespace Terminal42\UrlRewriteBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RouteCollection;
use Terminal42\UrlRewriteBundle\Terminal42UrlRewriteBundle;

class Plugin implements BundlePluginInterface, RoutingPluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(Terminal42UrlRewriteBundle::class)->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }

    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel): RouteCollection|null
    {
        $config = '@Terminal42UrlRewriteBundle/config/routing.yml';
        $loader = $resolver->resolve($config, 'yaml');

        if ($loader instanceof YamlFileLoader) {
            return $loader->load($config);
        }

        return null;
    }
}
