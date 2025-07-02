<?php

declare(strict_types=1);

namespace Terminal42\UrlRewriteBundle\ConfigProvider;

use Terminal42\UrlRewriteBundle\RewriteConfigInterface;

class ChainConfigProvider implements ConfigProviderInterface
{
    private const IDENTIFIER_SEPARATOR = '.';

    private array $providers = [];

    public function addProvider(ConfigProviderInterface $provider): void
    {
        $this->providers[] = $provider;
    }

    public function find(string $id): RewriteConfigInterface|null
    {
        [$providerIdentifier, $providerRewriteId] = explode(self::IDENTIFIER_SEPARATOR, $id);

        /** @var ConfigProviderInterface $provider */
        foreach ($this->providers as $provider) {
            if ($providerIdentifier === static::getProviderIdentifier($provider::class) && null !== ($config = $provider->find($providerRewriteId))) {
                return $config;
            }
        }

        return null;
    }

    public function findAll(): array
    {
        $configs = [];

        /** @var ConfigProviderInterface $provider */
        foreach ($this->providers as $provider) {
            $providerConfigs = $provider->findAll();

            /** @var RewriteConfigInterface $config */
            foreach ($providerConfigs as $config) {
                $config->setIdentifier(static::getConfigIdentifier($provider::class, $config->getIdentifier()));
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

        return strtolower($provider);
    }
}
