<?php

declare(strict_types=1);

namespace Terminal42\UrlRewriteBundle\EventListener;

use Contao\Backend;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Symfony\Cmf\Component\Routing\ChainRouterInterface;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;
use Terminal42\UrlRewriteBundle\QrCodeGenerator;
use Terminal42\UrlRewriteBundle\RewriteConfigInterface;

class RewriteContainerListener
{
    private readonly Filesystem $fs;

    private readonly ExpressionLanguage $expressionLanguage;

    public function __construct(
        private readonly QrCodeGenerator $qrCodeGenerator,
        private readonly RouterInterface $router,
        private readonly string $cacheDir,
        private readonly ContaoFramework $framework,
        private readonly ExpressionFunctionProviderInterface $expressionFunctionProvider,
        Filesystem|null $fs = null,
    ) {
        if (null === $fs) {
            $fs = new Filesystem();
        }
        $this->fs = $fs;
        $this->expressionLanguage = new ExpressionLanguage(null, [$this->expressionFunctionProvider]);
    }

    #[AsCallback('tl_url_rewrite', 'fields.requestCondition.save')]
    public function validateRequestCondition(string|null $value): mixed
    {
        if ($value) {
            $this->expressionLanguage->lint($value, null);
        }

        return $value;
    }

    #[AsCallback('tl_url_rewrite', 'config.onsubmit')]
    #[AsCallback('tl_url_rewrite', 'config.ondelete')]
    #[AsCallback('tl_url_rewrite', 'config.oncopy')]
    #[AsCallback('tl_url_rewrite', 'config.onrestore_version')]
    public function onRecordsModified(): void
    {
        $this->clearRouterCache();
    }

    #[AsCallback('tl_url_rewrite', 'fields.priority.save')]
    public function validatePriority(mixed $value): mixed
    {
        if (!preg_match('/^-?\d+$/', (string) $value)) {
            throw new \RuntimeException($GLOBALS['TL_LANG']['ERR']['digit']);
        }

        return $value;
    }

    #[AsCallback('tl_url_rewrite', 'fields.name.save')]
    public function onNameSaveCallback($value, DataContainer $dataContainer)
    {
        if ('' === $value) {
            $inputAdapter = $this->framework->getAdapter(Input::class);
            $value = $inputAdapter->post('requestPath') ?: $dataContainer->activeRecord->requestPath;
        }

        if ('' === $value) {
            throw new \InvalidArgumentException(\sprintf($GLOBALS['TL_LANG']['ERR']['mandatory'], $dataContainer->field));
        }

        return $value;
    }

    /**
     * Validate that request requirements contain valid regular expression.
     */
    #[AsCallback('tl_url_rewrite', 'fields.requestRequirements.save')]
    public function onRequestRequirementsSaveCallback(mixed $value): mixed
    {
        foreach (StringUtil::deserialize($value, true) as $regex) {
            try {
                if (false === preg_match('('.$regex['value'].')', '')) {
                    throw new \RuntimeException();
                }
            } catch (\Exception $e) {
                throw new \InvalidArgumentException(\sprintf($GLOBALS['TL_LANG']['tl_url_rewrite']['requestRequirements']['invalid'], $regex['key']), 0, $e);
            }
        }

        return $value;
    }

    #[AsCallback('tl_url_rewrite', 'list.label.label')]
    public function onGenerateLabel(array $row): string
    {
        if (410 === (int) $row['responseCode']) {
            $response = $row['responseCode'];
        } else {
            $response = \sprintf('%s, %s', $row['responseUri'], $row['responseCode']);
        }

        return \sprintf(
            '%s <span style="padding-left:3px;color:#b3b3b3;word-break:break-all;">[%s &rarr; %s (%s: %s)]</span>',
            $row['name'],
            $row['requestPath'],
            $response,
            $GLOBALS['TL_LANG']['tl_url_rewrite']['priority'][0],
            $row['priority'],
        );
    }

    #[AsCallback('tl_url_rewrite', 'fields.responseCode.options')]
    public function getResponseCodes(): array
    {
        $options = [];

        foreach (RewriteConfigInterface::VALID_RESPONSE_CODES as $code) {
            $options[$code] = $code.' '.Response::$statusTexts[$code];
        }

        return $options;
    }

    #[AsCallback('tl_url_rewrite', 'fields.examples.input_field')]
    public function generateExamples(): string
    {
        $buffer = '';

        if (\is_array($GLOBALS['TL_LANG']['tl_url_rewrite']['examplesRef'] ?? null)) {
            foreach ($GLOBALS['TL_LANG']['tl_url_rewrite']['examplesRef'] as $i => $example) {
                $buffer .= \sprintf(
                    '<h3>%s. %s</h3><pre style="margin-top:.5rem;padding:1rem;background:#7c7c9e12;font-size:.75rem;">%s</pre>',
                    $i + 1,
                    $example[0],
                    $example[1],
                );
            }
        }

        return \sprintf('<div class="widget long">%s</div>', $buffer);
    }

    #[AsCallback('tl_url_rewrite', 'list.operations.qrCode.button')]
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

            foreach ($this->router->all() as $router) {
                if ($router instanceof WarmableInterface) {
                    $router->warmUp($this->cacheDir);
                }
            }
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
        } catch (\InvalidArgumentException) {
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
