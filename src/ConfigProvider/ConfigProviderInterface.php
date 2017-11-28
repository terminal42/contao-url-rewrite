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

interface ConfigProviderInterface
{
    /**
     * Find the config.
     *
     * @param string $id
     *
     * @return RewriteConfigInterface|null
     */
    public function find(string $id): ?RewriteConfigInterface;

    /**
     * Find all configs.
     *
     * @return array
     */
    public function findAll(): array;
}
