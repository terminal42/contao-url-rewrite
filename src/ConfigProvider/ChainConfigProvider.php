<?php

/*
 * UrlRewrite Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2017, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

namespace Terminal42\UrlRewriteBundle\ConfigProvider;

use Terminal42\UrlRewriteBundle\RewriteConfigInterface;

class ChainConfigProvider implements ConfigProviderInterface
{
    /**
     * @var array
     */
    private $providers = [];

    /**
     * Add the config provider.
     *
     * @param ConfigProviderInterface $provider
     */
    public function addProvider(ConfigProviderInterface $provider): void
    {
        $this->providers[] = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function find(string $id): ?RewriteConfigInterface
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
     * {@inheritdoc}
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
