<?php

declare(strict_types = 1);

namespace Terminal42\UrlRewriteBundle\Routing;

use Contao\StringUtil;
use Doctrine\DBAL\Connection;
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
     * @inheritDoc
     */
    public function load($resource, $type = null): ?RouteCollection
    {
        if (true === $this->loaded) {
            throw new \RuntimeException('Do not add the "terminal42 url rewrite" loader twice');
        }

        $this->loaded = true;
        $collection = new RouteCollection();

        try {
            $rewrites = $this->db->fetchAll('SELECT * FROM tl_url_rewrite');
        } catch (PDOException $e) {
            return $collection;
        } catch (TableNotFoundException $e) {
            return $collection;
        }

        if (0 === count($rewrites)) {
            return $collection;
        }

        $count = 0;
        $collection = new RouteCollection();

        foreach ($rewrites as $rewrite) {
            /** @var Route $route */
            foreach ($this->generateRoutes($rewrite) as $route) {
                if ($route !== null) {
                    $collection->add('url_rewrite_' . $count++, $route);
                }
            }
        }

        return $collection;
    }

    /**
     * @inheritDoc
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
            /** @var array $requirements */
            $requirements = array_unique(array_filter(StringUtil::deserialize($config['requestRequirements'], true)));

            if (count($requirements) > 0) {
                foreach ($requirements as $requirement) {
                    list($key, $regex) = StringUtil::trimsplit(':', $requirement);
                    $route->setRequirement($key, $regex);
                }
            }
        }

        return $route;
    }
}
