<?php

declare(strict_types=1);

/*
 * UrlRewrite Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2021, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

namespace Terminal42\UrlRewriteBundle\Tests\EventListener;

use Symfony\Cmf\Component\Routing\ChainRouter;
use Symfony\Component\Routing\Router;

class RewriteContainerListenerWithCMFChainRouterTest extends AbstractContainerListenerTest
{
    protected function getRouter()
    {
        $router = $this->createMock(Router::class);

        $router
            ->method('getOption')
            ->willReturn('CacheClassOld')
        ;

        $chainRouter = new ChainRouter();
        $chainRouter->add($router);

        return $chainRouter;
    }
}
