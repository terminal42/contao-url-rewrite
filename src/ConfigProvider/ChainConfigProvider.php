<?php

declare(strict_types=1);

namespace Terminal42\UrlRewriteBundle\ConfigProvider;

use Terminal42\UrlRewriteBundle\RewriteConfigInterface;

class ChainConfigProvider implements ConfigProviderInterface
{
    private const IDENTIFIER_SEPARATOR = '.';

    /**
     * @var array
     */
    private $providers = [];

    /**
     * Add the config provider.
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
        [$providerIdentifier, $providerRewriteId] = explode(self::IDENTIFIER_SEPARATOR, $id);

        /** @var ConfigProviderInterface $provider */
        foreach ($this->providers as $provider) {
            if ($providerIdentifier === static::getProviderIdentifier(get_class($provider)) && null !== ($config = $provider->find($providerRewriteId))) {
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
            $providerConfigs = $provider->findAll();

            /** @var RewriteConfigInterface $config */
            foreach ($providerConfigs as $config) {
                $config->setIdentifier(static::getConfigIdentifier(get_class($provider), $config->getIdentifier()));
            }

            $configs = array_merge($configs, $providerConfigs);
        }

        return $configs;
    }

    /**
     * Get the config identifier.
     */
    public static function getConfigIdentifier(string $providerClass, string $configIdentifier): string
    {
        return static::getProviderIdentifier($providerClass).self::IDENTIFIER_SEPARATOR.$configIdentifier;
    }

    /**
     * Get the provider identifier.
     */
    private static function getProviderIdentifier(string $providerClass): string
    {
        $provider = str_replace('\\', '_', $providerClass);
        $provider = strtolower($provider);

        return $provider;
    }
}
