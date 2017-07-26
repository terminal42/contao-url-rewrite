<?php

/*
 * UrlRewrite Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2017, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

namespace Terminal42\UrlRewriteBundle\EventListener;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\InsertTags;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Filesystem\Filesystem;

class RewriteContainerListener
{
    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

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
     * @param ContaoFrameworkInterface $framework
     * @param Router     $router
     * @param string     $cacheDir
     * @param Filesystem $fs
     */
    public function __construct(ContaoFrameworkInterface $framework, Router $router, string $cacheDir, Filesystem $fs = null)
    {
        if ($fs === null) {
            $fs = new Filesystem();
        }

        $this->framework = $framework;
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
     * On response URI save.
     *
     * @param string $value
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function onResponseUriSave($value)
    {
        /** @var InsertTags $insertTags */
        $insertTags = $this->framework->createInstance(InsertTags::class);

        if (!preg_match('@^https?://@', $insertTags->replace($value))) {
            throw new \InvalidArgumentException($GLOBALS['TL_LANG']['tl_url_rewrite']['error.responseUriAbsolute']);
        }

        return $value;
    }

    /**
     * On generate the label
     *
     * @param array $row
     *
     * @return string
     */
    public function onGenerateLabel(array $row): string
    {
        $request = $row['requestPath'];

        if ((int) $row['responseCode'] === 410) {
            $response = $row['responseCode'];
        } else {
            $response = sprintf('%s, %s', $row['responseUri'], $row['responseCode']);
        }

        return sprintf(
            '%s <span style="padding-left:3px;color:#b3b3b3;">[%s &rarr; %s]</span>',
            $row['name'],
            $request,
            $response
        );
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
                    // @codeCoverageIgnoreStart
                    opcache_invalidate($file, true);
                    // @codeCoverageIgnoreEnd
                }

                $this->fs->remove($file);
            }
        }

        $this->router->warmUp($this->cacheDir);
    }
}
