<?php

declare(strict_types=1);

namespace Terminal42\UrlRewriteBundle\Tests\EventListener;

use Symfony\Component\Routing\Router;

class RewriteContainerListenerWithSymfonyRouterTest extends AbstractContainerListenerTest
{
    protected function getRouter()
    {
        $router = $this->createMock(Router::class);
        $router
            ->method('getOption')
            ->willReturn('CacheClassOld')
        ;

        return $router;
    }
}
