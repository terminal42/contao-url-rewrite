<?php

/*
 * UrlRewrite Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2017, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

namespace Terminal42\UrlRewriteBundle\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\Routing\RouterInterface;
use Terminal42\UrlRewriteBundle\RewriteConfigInterface;

class RewriteContainerListener
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var string
     */
    private $cacheDir;

    /**
     * RewriteContainerListener constructor.
     *
     * @param RouterInterface $router
     * @param string          $cacheDir
     */
    public function __construct(RouterInterface $router, string $cacheDir)
    {
        $this->router = $router;
        $this->cacheDir = $cacheDir;
    }

    /**
     * On records modified.
     */
    public function onRecordsModified(): void
    {
        $this->clearRouterCache();
    }

    /**
     * On inactive save callback.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function onInactiveSaveCallback($value)
    {
        $this->clearRouterCache();

        return $value;
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
        if ((int) $row['responseCode'] === 410) {
            $response = $row['responseCode'];
        } else {
            $response = sprintf('%s, %s', $row['responseUri'], $row['responseCode']);
        }

        return sprintf(
            '%s <span style="padding-left:3px;color:#b3b3b3;word-break:break-all;">[%s &rarr; %s]</span>',
            $row['name'],
            $row['requestPath'],
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

        foreach (RewriteConfigInterface::VALID_RESPONSE_CODES as $code) {
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
        if ($this->router instanceof WarmableInterface) {
            $this->router->warmUp($this->cacheDir);
        }

        // Clear the Zend OPcache
        if (function_exists('opcache_reset')) {
            // @codeCoverageIgnoreStart
            opcache_reset();
            // @codeCoverageIgnoreEnd
        }

        // Clear the APC OPcache
        if (function_exists('apc_clear_cache')) {
            // @codeCoverageIgnoreStart
            apc_clear_cache('opcode');
            // @codeCoverageIgnoreEnd
        }
    }
}
