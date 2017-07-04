<?php

declare(strict_types=1);

/*
 * UrlRewrite Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2017, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

namespace Terminal42\UrlRewriteBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Contao\StringUtil;
use Psr\Container\ContainerInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Terminal42\UrlRewriteBundle\Terminal42UrlRewriteBundle;

class Plugin implements BundlePluginInterface, RoutingPluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(Terminal42UrlRewriteBundle::class)->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel): ?RouteCollection
    {
        $container = $kernel->getContainer();
        $db = $container->get('database_connection');

        if (!$db->isConnected()) {
            return null;
        }

        $rewrites = $db->fetchAll('SELECT * FROM tl_url_rewrite');

        if (0 === count($rewrites)) {
            return null;
        }

        $count = 0;
        $collection = new RouteCollection();

        foreach ($rewrites as $rewrite) {
            /** @var Route $route */
            foreach ($this->generateRoutes($rewrite, $container) as $route) {
                if ($route !== null) {
                    $collection->add('url_rewrite_'.$count++, $route);
                }
            }
        }

        return $collection;
    }

    /**
     * Generate the routes.
     *
     * @param array              $config
     * @param ContainerInterface $container
     *
     * @return \Generator
     */
    private function generateRoutes(array $config, ContainerInterface $container): \Generator
    {
        /** @var StringUtil $stringUtil */
        $stringUtil = $container->get('contao.framework')->getAdapter(StringUtil::class);

        $hosts = [];

        // Parse the hosts from config
        if (isset($config['requestHosts'])) {
            /** @var array $hosts */
            $hosts = array_unique(array_filter($stringUtil->deserialize($config['requestHosts'], true)));
        }

        if (count($hosts) > 0) {
            foreach ($hosts as $host) {
                yield $this->createRoute($config, $container, $host);
            }
        } else {
            yield $this->createRoute($config, $container);
        }
    }

    /**
     * Create the route object.
     *
     * @param array              $config
     * @param ContainerInterface $container
     * @param string|null        $host
     *
     * @return Route|null
     */
    private function createRoute(array $config, ContainerInterface $container, string $host = null): ?Route
    {
        if (!isset($config['id'], $config['requestPath'])) {
            return null;
        }

        $route = new Route($config['requestPath']);
        $route->setMethods('GET');
        $route->setDefault('_controller', 'terminal42_url_rewrite.rewrite_controller:indexAction');
        $route->setDefault('_url_rewrite', $config['id']);

        // Set the host
        if (null !== $host) {
            $route->setHost($host);
        }

        // Set the scheme
        if (isset($config['requestScheme'])) {
            $route->setSchemes($config['requestScheme']);
        }

        // Set the requirements
        if (isset($config['requestRequirements'])) {
            /** @var StringUtil $stringUtil */
            $stringUtil = $container->get('contao.framework')->getAdapter(StringUtil::class);

            /** @var array $requirements */
            $requirements = array_unique(array_filter($stringUtil->deserialize($config['requestRequirements'], true)));

            if (count($requirements) > 0) {
                foreach ($requirements as $requirement) {
                    list($key, $regex) = $stringUtil->trimsplit(':', $requirement);
                    $route->setRequirement($key, $regex);
                }
            }
        }

        return $route;
    }
}
