<?php

namespace Terminal42\UrlRewriteBundle\Tests\EventListener;

use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
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

        $insertTags = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['replace'])
            ->getMock()
        ;

        $insertTags
            ->method('replace')
            ->willReturnCallback(function ($param) {
                return $param;
            })
        ;

        $framework = $this->createMock(ContaoFramework::class);

        $framework
            ->method('createInstance')
            ->willReturn($insertTags)
        ;

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

        $this->listener = new RewriteContainerListener($framework, $router, $this->cacheDir);
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

    public function testOnResponseUriSaveError()
    {
        $GLOBALS['TL_LANG'] = ['tl_url_rewrite' => ['error.responseUriAbsolute' => 'foobar']];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('foobar');

        $this->listener->onResponseUriSave('foobar');
    }

    public function testOnResponseUriSave()
    {
        $this->assertSame('http://domain.tld', $this->listener->onResponseUriSave('http://domain.tld'));
    }

    /**
     * @dataProvider onGenerateLabelDataProvider
     */
    public function testOnGenerateLabel($provided, $expected)
    {
        $this->assertSame($expected, $this->listener->onGenerateLabel($provided));
    }

    public function onGenerateLabelDataProvider()
    {
        return [
            301 => [
                [
                    'name' => 'Foobar',
                    'requestPath' => 'foo/bar',
                    'responseUri' => 'http://domain.tld/baz/{bar}',
                    'responseCode' => 301,
                ],
                'Foobar <span style="padding-left:3px;color:#b3b3b3;">[foo/bar &rarr; http://domain.tld/baz/{bar}, 301]</span>',
            ],
            410 => [
                [
                    'name' => 'Foobar',
                    'requestPath' => 'foo/bar',
                    'responseCode' => 410,
                ],
                'Foobar <span style="padding-left:3px;color:#b3b3b3;">[foo/bar &rarr; 410]</span>',
            ]
        ];
    }
}
