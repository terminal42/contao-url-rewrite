<?php

declare(strict_types=1);

namespace Terminal42\UrlRewriteBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Terminal42\UrlRewriteBundle\DependencyInjection\Compiler\ConfigProviderPass;

class Terminal42UrlRewriteBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new ConfigProviderPass(
            'terminal42_url_rewrite.provider',
            'terminal42_url_rewrite.provider.chain',
            'terminal42_url_rewrite.provider',
        ));
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
