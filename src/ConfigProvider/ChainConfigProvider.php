<?php

namespace Terminal42\UrlRewriteBundle\ConfigProvider;

use Terminal42\UrlRewriteBundle\RewriteConfig;

class ChainConfigProvider implements ConfigProviderInterface
{
    /**
     * @var array
     */
    private $providers = [];

    /**
     * Add the config provider
     *
     * @param ConfigProviderInterface $provider
     */
    public function addProvider(ConfigProviderInterface $provider): void
    {
        $this->providers[] = $provider;
    }

    /**
     * @inheritDoc
     */
    public function find(string $id): ?RewriteConfig
    {
        /** @var ConfigProviderInterface $provider */
        foreach ($this->providers as $provider) {
            if (($config = $provider->find($id)) !== null) {
                return $config;
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function findAll(): array
    {
        $configs = [];

        /** @var ConfigProviderInterface $provider */
        foreach ($this->providers as $provider) {
            $configs = array_merge($configs, $provider->findAll());
        }

        return $configs;
    }
}
