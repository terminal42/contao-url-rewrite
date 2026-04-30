<?php

declare(strict_types=1);

namespace Terminal42\UrlRewriteBundle\Tests\EventListener;

use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;

final class RewriteContainerListenerWithSymfonyRouterTest extends AbstractContainerListenerTestCase
{
    protected function getRouter(): RouterInterface
    {
        $router = $this->createStub(Router::class);
        $router
            ->method('getOption')
            ->willReturn('CacheClassOld')
        ;

        return $router;
    }
}
