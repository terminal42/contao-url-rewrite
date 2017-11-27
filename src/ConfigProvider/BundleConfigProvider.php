<?php

namespace Terminal42\UrlRewriteBundle\ConfigProvider;

use Terminal42\UrlRewriteBundle\RewriteConfig;

class BundleConfigProvider implements ConfigProviderInterface
{
    /**
     * @var array
     */
    private $entries = [];

    /**
     * @var string
     */
    private $key = 'bundle';

    /**
     * BundleConfigProvider constructor.
     *
     * @param array $entries
     */
    public function __construct(array $entries = [])
    {
        $this->entries = $entries;
    }

    /**
     * @inheritDoc
     */
    public function find(string $id): ?RewriteConfig
    {
        list($key, $id) = explode(':', $id);

        // Return if the key is not supported
        if ($key !== $this->key) {
            return null;
        }

        // Return if the entry does not exist
        if (!array_key_exists($id, $this->entries)) {
            return null;
        }

        return $this->createConfig($id, $this->entries[$id]);
    }

    /**
     * @inheritDoc
     */
    public function findAll(): array
    {
        if (count($this->entries) === 0) {
            return [];
        }

        $configs = [];

        foreach ($this->entries as $id => $entry) {
            if (($config = $this->createConfig($id, $entry)) !== null) {
                $configs[] = $config;
            }
        }

        return $configs;
    }

    /**
     * Create the config
     *
     * @param int   $id
     * @param array $data
     *
     * @return null|RewriteConfig
     */
    private function createConfig(int $id, array $data): ?RewriteConfig
    {
        if (!isset($data['request']['path'], $data['response']['code'])) {
            return null;
        }

        $config = new RewriteConfig($this->key . ':' . $id, $data['request']['path'], (int) $data['response']['code']);

        // Request hosts
        if (isset($data['request']['hosts'])) {
            $config->setRequestHosts($data['request']['hosts']);
        }

        // Request condition
        if (isset($data['request']['condition'])) {
            $config->setRequestCondition($data['request']['condition']);
        }

        // Request requirements
        if (isset($data['request']['requirements'])) {
            $config->setRequestRequirements($data['request']['requirements']);
        }

        // Response URI
        if (isset($data['response']['uri'])) {
            $config->setResponseUri($data['response']['uri']);
        }

        return $config;
    }
}
