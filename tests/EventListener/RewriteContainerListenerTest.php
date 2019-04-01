<?php

namespace Terminal42\UrlRewriteBundle\Tests\EventListener;

use Contao\CoreBundle\Framework\Adapter;
use Contao\DataContainer;
use Contao\Input;
use Contao\TestCase\ContaoTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Filesystem\Filesystem;
use Terminal42\UrlRewriteBundle\EventListener\RewriteContainerListener;

class RewriteContainerListenerTest extends ContaoTestCase
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

    /**
     * @var Input|Adapter|MockObject
     */
    private $inputAdapter;

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

        $this->inputAdapter = $this->mockAdapter(['post']);
        $framework = $this->mockContaoFramework([Input::class => $this->inputAdapter]);

        $this->listener = new RewriteContainerListener($router, $this->cacheDir, $this->fs, $framework);
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

    public function testOnInactiveSaveCallback()
    {
        $this->assertSame(1, $this->listener->onInactiveSaveCallback(1));
        $this->assertTrue($this->fs->exists($this->cacheDir.'/CacheClassNew.php'));
    }

    public function testOnNameSaveCallback()
    {
        $dataContainer = $this->createMock(DataContainer::class);
        $dataContainer->method('__get')->with('activeRecord')->willReturn((object) [ 'requestPath' => 'Bar']);

        $this->assertSame('Foo', $this->listener->onNameSaveCallback('Foo', $dataContainer));
        $this->assertSame('Bar', $this->listener->onNameSaveCallback('', $dataContainer));

        $this->inputAdapter->method('post')->willReturn('Baz');
        $this->assertSame('Baz', $this->listener->onNameSaveCallback('', $dataContainer));
    }

    public function testOnNameSaveCallbackThrowingException()
    {
        $GLOBALS['TL_LANG']['ERR']['mandatory'] = '';

        $dataContainer = $this->createMock(DataContainer::class);
        $dataContainer->method('__get')
            ->withConsecutive(['activeRecord'], ['field'])
            ->willReturnOnConsecutiveCalls((object) [ 'requestPath' => ''], 'field');

        $this->expectException(InvalidArgumentException::class);
        $this->listener->onNameSaveCallback('', $dataContainer);
    }

    /**
     * @dataProvider onGenerateLabelDataProvider
     */
    public function testOnGenerateLabel($provided, $expected)
    {
        $GLOBALS['TL_LANG']['tl_url_rewrite']['priority'][0] = 'Priority';

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
                    'priority' => 0
                ],
                'Foobar <span style="padding-left:3px;color:#b3b3b3;word-break:break-all;">[foo/bar &rarr; http://domain.tld/baz/{bar}, 301 (Priority: 0)]</span>',
            ],
            410 => [
                [
                    'name' => 'Foobar',
                    'requestPath' => 'foo/bar',
                    'responseCode' => 410,
                    'priority' => 10
                ],
                'Foobar <span style="padding-left:3px;color:#b3b3b3;word-break:break-all;">[foo/bar &rarr; 410 (Priority: 10)]</span>',
            ]
        ];
    }

    public function testOnGenerateExamples()
    {
        $GLOBALS['TL_LANG'] = [
            'tl_url_rewrite' => [
                'examples' => [
                    ['foo', 'bar']
                ]
            ],
        ];

        $buffer = $this->listener->generateExamples();

        $this->assertStringStartsWith('<div class="widget long">', $buffer);
        $this->assertStringEndsWith('</div>', $buffer);
    }

    public function testOnGetResponseCodes()
    {
        $expected = [
            301 => '301 Moved Permanently',
            302 => '302 Found',
            303 => '303 See Other',
            307 => '307 Temporary Redirect',
            410 => '410 Gone',
        ];

        $this->assertSame($expected, $this->listener->getResponseCodes());
    }
}
