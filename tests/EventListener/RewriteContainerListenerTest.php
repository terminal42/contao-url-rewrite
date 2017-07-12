<?php

namespace Terminal42\UrlRewriteBundle\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Filesystem\Filesystem;
use Terminal42\UrlRewriteBundle\EventListener\RewriteContainerListener;

class RewriteContainerListenerTest extends TestCase
{
    /**
     * @var RewriteContainerListener
     */
    private $listener;

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var string
     */
    private $cacheDir;

    protected function setUp()
    {
        $this->cacheDir = __DIR__ . '/tmp';

        $this->fs = new Filesystem();
        $this->fs->mkdir($this->cacheDir);

        $router = $this->createMock(Router::class);

        $router
            ->method('getOption')
            ->willReturn('CacheClassOld')
        ;

        $router
            ->method('warmUp')
            ->willReturnCallback(
                function () {
                    $this->fs->touch($this->cacheDir . '/CacheClassNew.php');
                }
            )
        ;

        $this->listener = new RewriteContainerListener($router, $this->cacheDir);
    }

    protected function tearDown()
    {
        $this->fs->remove($this->cacheDir);
    }

    public function testInstantiation()
    {
        $this->assertInstanceOf(RewriteContainerListener::class, $this->listener);
    }

    public function testOnRecordsModified()
    {
        $this->fs->touch($this->cacheDir . '/CacheClassOld.php');
        $this->listener->onRecordsModified();

        $this->assertFalse($this->fs->exists($this->cacheDir.'/CacheClassOld.php'));
        $this->assertTrue($this->fs->exists($this->cacheDir.'/CacheClassNew.php'));
    }
}
