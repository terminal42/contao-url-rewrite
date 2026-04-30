<?php

declare(strict_types=1);

namespace Terminal42\UrlRewriteBundle\Tests\EventListener;

use Symfony\Cmf\Component\Routing\ChainRouter;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;

final class RewriteContainerListenerWithCMFChainRouterTest extends AbstractContainerListenerTestCase
{
    protected function getRouter(): RouterInterface
    {
        $router = $this->createStub(Router::class);
        $router
            ->method('getOption')
            ->willReturn('CacheClassOld')
        ;

        $chainRouter = new ChainRouter();
        $chainRouter->add($router);

        return $chainRouter;
    }
}
