<?php

declare(strict_types=1);

namespace Terminal42\UrlRewriteBundle\ConfigProvider;

use Terminal42\UrlRewriteBundle\Exception\TemporarilyUnavailableConfigProviderException;
use Terminal42\UrlRewriteBundle\RewriteConfigInterface;

interface ConfigProviderInterface
{
    /**
     * Find the config.
     *
     * @throws TemporarilyUnavailableConfigProviderException
     */
    public function find(string $id): RewriteConfigInterface|null;

    /**
     * Find all configs.
     */
    public function findAll(): array;
}
