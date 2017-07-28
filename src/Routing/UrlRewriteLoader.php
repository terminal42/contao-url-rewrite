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

use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class UrlRewriteLoader extends Loader
{
    /**
     * @var Connection
     */
    private $db;

    /**
     * Has been already loaded?
     *
     * @var bool
     */
    private $loaded = false;

    /**
     * UrlRewriteLoader constructor.
     *
     * @param Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
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

        try {
            $rewrites = $this->db->fetchAll('SELECT * FROM tl_url_rewrite');
        } catch (\PDOException | TableNotFoundException $e) {
            return $collection;
        }

        if (0 === count($rewrites)) {
            return $collection;
        }

        $count = 0;

        foreach ($rewrites as $rewrite) {
            /** @var Route $route */
            foreach ($this->generateRoutes($rewrite) as $route) {
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
     * @param array $config
     *
     * @return \Generator
     */
    private function generateRoutes(array $config): \Generator
    {
        $hosts = [];

        // Parse the hosts from config
        if (isset($config['requestHosts'])) {
            /** @var array $hosts */
            $hosts = array_unique(array_filter(StringUtil::deserialize($config['requestHosts'], true)));
        }

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
     * @param array       $config
     * @param string|null $host
     *
     * @return Route|null
     */
    private function createRoute(array $config, string $host = null): ?Route
    {
        if (!isset($config['id'], $config['type'], $config['requestPath'])) {
            return null;
        }

        switch ($config['type']) {
            case 'basic':
                $route = $this->createBasicRoute($config);
                break;
            case 'expert':
                $route = $this->createExpertRoute($config);
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Unsupported database record config type: %s', $config['type']));
        }

        $route->setDefault('_controller', 'terminal42_url_rewrite.rewrite_controller:indexAction');
        $route->setDefault('_url_rewrite', $config['id']);

        // Set the host
        if (null !== $host) {
            $route->setHost($host);
        }

        return $route;
    }

    /**
     * Create the basic route.
     *
     * @param array $config
     *
     * @return Route
     */
    private function createBasicRoute(array $config): Route
    {
        $route = new Route($config['requestPath']);
        $route->setMethods('GET');

        // Set the requirements
        if (isset($config['requestRequirements'])) {
            /** @var array $requirements */
            $requirements = StringUtil::deserialize($config['requestRequirements'], true);
            $requirements = array_filter($requirements, function ($item) {
                return $item['key'] !== '' && $item['value'] !== '';
            });

            if (count($requirements) > 0) {
                foreach ($requirements as $requirement) {
                    $route->setRequirement($requirement['key'], $requirement['value']);
                }
            }
        }

        return $route;
    }

    /**
     * Create the expert route.
     *
     * @param array $config
     *
     * @return Route
     */
    private function createExpertRoute(array $config): Route
    {
        $route = new Route($config['requestPath']);
        $route->setCondition($config['requestCondition']);

        return $route;
    }
}
