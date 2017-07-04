<?php

declare(strict_types=1);

/*
 * UrlRewrite Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2017, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

namespace Terminal42\UrlRewriteBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class Terminal42UrlRewriteBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
    }
}
