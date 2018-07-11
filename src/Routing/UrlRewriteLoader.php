<?php

declare(strict_types=1);

/*
 * UrlRewrite Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2017, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

namespace Terminal42\UrlRewriteBundle\Routing;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Terminal42\UrlRewriteBundle\ConfigProvider\ConfigProviderInterface;
use Terminal42\UrlRewriteBundle\RewriteConfigInterface;

class UrlRewriteLoader extends Loader
{
    /**
     * @var ConfigProviderInterface
     */
    private $configProvider;

    /**
     * Has been already loaded?
     *
     * @var bool
     */
    private $loaded = false;

    /**
     * UrlRewriteLoader constructor.
     *
     * @param ConfigProviderInterface $configProvider
     */
    public function __construct(ConfigProviderInterface $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $type = null): RouteCollection
    {
        if (true === $this->loaded) {
            throw new \RuntimeException('Do not add the "terminal42 url rewrite" loader twice');
        }

        $this->loaded = true;
        $collection = new RouteCollection();
        $configs = $this->configProvider->findAll();

        if (0 === count($configs)) {
            return $collection;
        }

        $count = 0;

        /** @var RewriteConfigInterface $config */
        foreach ($configs as $config) {
            /** @var Route $route */
            foreach ($this->generateRoutes($config) as $route) {
                if ($route !== null) {
                    $collection->add('url_rewrite_'.$count++, $route);
                }
            }
        }

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null): bool
    {
        return 'terminal42_url_rewrite' === $type;
    }

    /**
     * Generate the routes.
     *
     * @param RewriteConfigInterface $config
     *
     * @return \Generator
     */
    private function generateRoutes(RewriteConfigInterface $config): \Generator
    {
        $hosts = $config->getRequestHosts();

        if (count($hosts) > 0) {
            foreach ($hosts as $host) {
                yield $this->createRoute($config, $host);
            }
        } else {
            yield $this->createRoute($config);
        }
    }

    /**
     * Create the route object.
     *
     * @param RewriteConfigInterface $config
     * @param string|null   $host
     *
     * @return Route|null
     */
    private function createRoute(RewriteConfigInterface $config, string $host = null): ?Route
    {
        if (!$config->getRequestPath()) {
            return null;
        }

        $route = new Route(rawurldecode($config->getRequestPath()));
        $route->setDefault('_controller', 'terminal42_url_rewrite.rewrite_controller:indexAction');
        $route->setDefault('_url_rewrite', $config->getIdentifier());
        $route->setRequirements($config->getRequestRequirements());

        // Set the condition
        if (($condition = $config->getRequestCondition()) !== null) {
            $route->setCondition($condition);
        } else {
            $route->setMethods('GET');
        }

        // Set the host
        if (null !== $host) {
            $route->setHost($host);
        }

        return $route;
    }
}
