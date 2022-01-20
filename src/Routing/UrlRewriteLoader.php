<?php

declare(strict_types=1);

/*
 * UrlRewrite Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2021, terminal42 gmbh
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
     * Attribute name.
     */
    public const ATTRIBUTE_NAME = '_url_rewrite';

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

    public function __construct(ConfigProviderInterface $configProvider)
    {
        // Do not call parent constructor, it does not exist in Symfony < 5

        $this->configProvider = $configProvider;
    }

    public function load($resource, $type = null): RouteCollection
    {
        if (true === $this->loaded) {
            throw new \RuntimeException('Do not add the "terminal42 url rewrite" loader twice');
        }

        $this->loaded = true;
        $collection = new RouteCollection();
        $configs = $this->configProvider->findAll();

        if (0 === \count($configs)) {
            return $collection;
        }

        $count = 0;

        /** @var RewriteConfigInterface $config */
        foreach ($configs as $config) {
            /** @var Route $route */
            foreach ($this->generateRoutes($config) as $route) {
                if (null !== $route) {
                    $collection->add('url_rewrite_'.$count++, $route);
                }
            }
        }

        return $collection;
    }

    public function supports($resource, $type = null): bool
    {
        return 'terminal42_url_rewrite' === $type;
    }

    /**
     * Generate the routes.
     */
    private function generateRoutes(RewriteConfigInterface $config): \Generator
    {
        $hosts = $config->getRequestHosts();

        if (\count($hosts) > 0) {
            foreach ($hosts as $host) {
                yield $this->createRoute($config, $host);
            }
        } else {
            yield $this->createRoute($config);
        }
    }

    /**
     * Create the route object.
     */
    private function createRoute(RewriteConfigInterface $config, string $host = null): ?Route
    {
        if (!$config->getRequestPath()) {
            return null;
        }

        $route = new Route(rawurldecode($config->getRequestPath()));
        $route->setDefault('_controller', 'terminal42_url_rewrite.rewrite_controller:indexAction');
        $route->setDefault(self::ATTRIBUTE_NAME, $config->getIdentifier());
        $route->setOption('utf8', true);
        $route->setRequirements($config->getRequestRequirements());

        // Set the condition
        if (null !== ($condition = $config->getRequestCondition())) {
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
