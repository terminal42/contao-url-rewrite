<?php

/*
 * UrlRewrite Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2017, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

namespace Terminal42\UrlRewriteBundle\EventListener;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Filesystem\Filesystem;

class RewriteContainerListener
{
    /**
     * @var Router
     */
    private $router;

    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * RewriteContainerListener constructor.
     *
     * @param Router     $router
     * @param string     $cacheDir
     * @param Filesystem $fs
     */
    public function __construct(Router $router, string $cacheDir, Filesystem $fs = null)
    {
        if ($fs === null) {
            $fs = new Filesystem();
        }

        $this->router = $router;
        $this->cacheDir = $cacheDir;
        $this->fs = $fs;
    }

    /**
     * On records modified.
     */
    public function onRecordsModified(): void
    {
        $this->clearRouterCache();
    }

    /**
     * Clear the router cache.
     */
    private function clearRouterCache(): void
    {
        foreach (['generator_cache_class', 'matcher_cache_class'] as $option) {
            $class = $this->router->getOption($option);
            $file = $this->cacheDir.DIRECTORY_SEPARATOR.$class.'.php';

            if ($this->fs->exists($file)) {
                // Clear the OPcache
                if (function_exists('opcache_invalidate')) {
                    opcache_invalidate($file, true);
                }

                $this->fs->remove($file);
            }
        }

        $this->router->warmUp($this->cacheDir);
    }
}
