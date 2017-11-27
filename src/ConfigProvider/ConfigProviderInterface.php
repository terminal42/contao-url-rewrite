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

interface ConfigProviderInterface
{
    /**
     * Find the config.
     *
     * @param string $id
     *
     * @return RewriteConfig|null
     */
    public function find(string $id): ?RewriteConfig;

    /**
     * Find all configs.
     *
     * @return array
     */
    public function findAll(): array;
}
