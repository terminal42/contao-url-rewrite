<?php

declare(strict_types=1);

namespace Terminal42\UrlRewriteBundle\EventListener;

use Contao\Backend;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Symfony\Cmf\Component\Routing\ChainRouterInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;
use Terminal42\UrlRewriteBundle\QrCodeGenerator;
use Terminal42\UrlRewriteBundle\RewriteConfigInterface;

class RewriteContainerListener
{
    /**
     * @var QrCodeGenerator
     */
    private $qrCodeGenerator;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var Filesystem
     */
    private $fs;

    public function __construct(QrCodeGenerator $qrCodeGenerator, RouterInterface $router, string $cacheDir, ContaoFramework $framework, Filesystem $fs = null)
    {
        if (null === $fs) {
            $fs = new Filesystem();
        }

        $this->qrCodeGenerator = $qrCodeGenerator;
        $this->router = $router;
        $this->cacheDir = $cacheDir;
        $this->fs = $fs;
        $this->framework = $framework;
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
     * On name save callback.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function onNameSaveCallback($value, DataContainer $dataContainer)
    {
        if ('' === $value) {
            $inputAdapter = $this->framework->getAdapter(Input::class);
            $value = $inputAdapter->post('requestPath') ?: $dataContainer->activeRecord->requestPath;
        }

        if ('' === $value) {
            throw new \InvalidArgumentException(sprintf($GLOBALS['TL_LANG']['ERR']['mandatory'], $dataContainer->field));
        }

        return $value;
    }

    /**
     * Validate that request requirements contain valid regular expression.
     */
    public function onRequestRequirementsSaveCallback($value)
    {
        foreach (StringUtil::deserialize($value, true) as $regex) {
            try {
                if (false === preg_match('('.$regex['value'].')', '')) {
                    throw new \RuntimeException();
                }
            } catch (\Exception $e) {
                throw new \InvalidArgumentException(sprintf($GLOBALS['TL_LANG']['tl_url_rewrite']['requestRequirements']['invalid'], $regex['key']), 0, $e);
            }
        }

        return $value;
    }

    /**
     * On generate the label.
     */
    public function onGenerateLabel(array $row): string
    {
        if (410 === (int) $row['responseCode']) {
            $response = $row['responseCode'];
        } else {
            $response = sprintf('%s, %s', $row['responseUri'], $row['responseCode']);
        }

        return sprintf(
            '%s <span style="padding-left:3px;color:#b3b3b3;word-break:break-all;">[%s &rarr; %s (%s: %s)]</span>',
            $row['name'],
            $row['requestPath'],
            $response,
            $GLOBALS['TL_LANG']['tl_url_rewrite']['priority'][0],
            $row['priority']
        );
    }

    /**
     * Get the response codes.
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
     * On QR code button callback.
     */
    public function onQrCodeButtonCallback(array $row, string $href, string $label, string $title, string $icon, string $attributes): string
    {
        return $this->qrCodeGenerator->validate($row) ? '<a href="'.Backend::addToUrl($href.'&amp;id='.$row['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)).' ';
    }

    /**
     * Clear the router cache.
     */
    private function clearRouterCache(): void
    {
        // Search Symfony router in CMF ChainRouter (Contao 4.7+)
        if ($this->router instanceof ChainRouterInterface) {
            foreach ($this->router->all() as $router) {
                if ($router instanceof Router) {
                    $this->clearSymfonyRouterCache($router);
                }
            }
        }

        // Regular Symfony router (Contao 4.4+)
        if ($this->router instanceof Router) {
            $this->clearSymfonyRouterCache($this->router);
        }

        if ($this->router instanceof WarmableInterface) {
            $this->router->warmUp($this->cacheDir);
        }

        // Clear the Zend OPcache
        if (\function_exists('opcache_reset')) {
            // @codeCoverageIgnoreStart
            opcache_reset();
            // @codeCoverageIgnoreEnd
        }

        // Clear the APC OPcache
        if (\function_exists('apc_clear_cache')) {
            // @codeCoverageIgnoreStart
            apc_clear_cache('opcode');
            // @codeCoverageIgnoreEnd
        }
    }

    private function clearSymfonyRouterCache(Router $router): void
    {
        try {
            $cacheClasses = [];

            foreach (['generator_cache_class', 'matcher_cache_class'] as $option) {
                $cacheClasses[] = $router->getOption($option);
            }
        } catch (\InvalidArgumentException $exception) {
            $cacheClasses = ['url_generating_routes', 'url_matching_routes'];
        }

        foreach ($cacheClasses as $class) {
            $file = $this->cacheDir.\DIRECTORY_SEPARATOR.$class.'.php';

            if ($this->fs->exists($file)) {
                $this->fs->remove($file);
            }
        }
    }
}
