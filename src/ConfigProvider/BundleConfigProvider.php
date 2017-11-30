<?php

/*
 * UrlRewrite Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2017, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

namespace Terminal42\UrlRewriteBundle\ConfigProvider;

use Terminal42\UrlRewriteBundle\RewriteConfig;
use Terminal42\UrlRewriteBundle\RewriteConfigInterface;

class BundleConfigProvider implements ConfigProviderInterface
{
    /**
     * @var array
     */
    private $entries = [];

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
     * {@inheritdoc}
     */
    public function find(string $id): ?RewriteConfigInterface
    {
        if (!array_key_exists($id, $this->entries)) {
            return null;
        }

        return $this->createConfig($id, $this->entries[$id]);
    }

    /**
     * {@inheritdoc}
     */
    public function findAll(): array
    {
        if (count($this->entries) === 0) {
            return [];
        }

        $configs = [];

        foreach ($this->entries as $id => $entry) {
            if (($config = $this->createConfig((string) $id, $entry)) !== null) {
                $configs[] = $config;
            }
        }

        return $configs;
    }

    /**
     * Create the config.
     *
     * @param string $id
     * @param array  $data
     *
     * @return null|RewriteConfig
     */
    private function createConfig(string $id, array $data): ?RewriteConfig
    {
        if (!isset($data['request']['path'], $data['response']['code'])) {
            return null;
        }

        $config = new RewriteConfig($id, $data['request']['path'], (int) $data['response']['code']);

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
