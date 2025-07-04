<?php

declare(strict_types=1);

namespace Terminal42\UrlRewriteBundle\ConfigProvider;

use Terminal42\UrlRewriteBundle\RewriteConfig;
use Terminal42\UrlRewriteBundle\RewriteConfigInterface;

class BundleConfigProvider implements ConfigProviderInterface
{
    public function __construct(private array $entries = [])
    {
    }

    public function find(string $id): RewriteConfigInterface|null
    {
        if (!\array_key_exists($id, $this->entries)) {
            return null;
        }

        return $this->createConfig($id, $this->entries[$id]);
    }

    public function findAll(): array
    {
        if (0 === \count($this->entries)) {
            return [];
        }

        $configs = [];

        foreach ($this->entries as $id => $entry) {
            if (null !== ($config = $this->createConfig((string) $id, $entry))) {
                $configs[] = $config;
            }
        }

        return $configs;
    }

    /**
     * Create the config.
     */
    private function createConfig(string $id, array $data): RewriteConfig|null
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

        // Conditional response URIs
        $config->setConditionalResponseUris($data['response']['conditionalUris'] ?? []);

        // Response URI
        if (isset($data['response']['uri'])) {
            $config->setResponseUri($data['response']['uri']);
        }

        // Keep query parameters
        if (isset($data['response']['keepQueryParams'])) {
            $config->setKeepQueryParams($data['response']['keepQueryParams']);
        }

        return $config;
    }
}
