<?php

namespace Terminal42\UrlRewriteBundle\ConfigProvider;

use Terminal42\UrlRewriteBundle\RewriteConfig;

interface ConfigProviderInterface
{
    /**
     * Find the config
     *
     * @param string $id
     *
     * @return RewriteConfig|null
     */
    public function find(string $id): ?RewriteConfig;

    /**
     * Find all configs
     *
     * @return array
     */
    public function findAll(): array;
}
