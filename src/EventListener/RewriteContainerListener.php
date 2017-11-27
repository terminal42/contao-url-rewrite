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
use Symfony\Component\HttpFoundation\Response;
use Terminal42\UrlRewriteBundle\RewriteConfig;

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
     * On generate the label.
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
     * Get the response codes.
     *
     * @return array
     */
    public function getResponseCodes(): array
    {
        $options = [];

        foreach (RewriteConfig::VALID_RESPONSE_CODES as $code) {
            $options[$code] = $code.' '.Response::$statusTexts[$code];
        }

        return $options;
    }

    /**
     * Generate the examples.
     *
     * @return string
     */
    public function generateExamples(): string
    {
        $buffer = '';

        foreach ($GLOBALS['TL_LANG']['tl_url_rewrite']['examples'] as $i => $example) {
            $buffer .= sprintf(
                '<h3>%s. %s</h3><pre style="margin-top:.5rem;padding:1rem;background:#f6f6f8;font-size:.75rem;">%s</pre>',
                $i + 1,
                $example[0],
                $example[1]
            );
        }

        return sprintf('<div class="widget long">%s</div>', $buffer);
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
