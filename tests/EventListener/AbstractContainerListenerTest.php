<?php

declare(strict_types=1);

namespace Terminal42\UrlRewriteBundle\Tests\EventListener;

use Contao\CoreBundle\Framework\Adapter;
use Contao\DataContainer;
use Contao\Input;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\RouterInterface;
use Terminal42\UrlRewriteBundle\EventListener\RewriteContainerListener;
use Terminal42\UrlRewriteBundle\QrCodeGenerator;

abstract class AbstractContainerListenerTest extends ContaoTestCase
{
    /**
     * @var RewriteContainerListener
     */
    protected $listener;

    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * @var string
     */
    protected $cacheDir;

    /**
     * @var QrCodeGenerator
     */
    protected $qrCodeGenerator;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var Input|Adapter|MockObject
     */
    private $inputAdapter;

    protected function setUp(): void
    {
        $this->cacheDir = __DIR__.'/tmp';

        $this->fs = new Filesystem();
        $this->fs->mkdir($this->cacheDir);

        $this->qrCodeGenerator = $this->createMock(QrCodeGenerator::class);

        $this->router = $this->getRouter();

        $this->inputAdapter = $this->mockAdapter(['post']);
        $framework = $this->mockContaoFramework([Input::class => $this->inputAdapter]);

        $this->listener = new RewriteContainerListener($this->qrCodeGenerator, $this->router, $this->cacheDir, $framework);
    }

    protected function tearDown(): void
    {
        $this->fs->remove($this->cacheDir);
    }

    public function testInstantiation(): void
    {
        $this->assertInstanceOf(RewriteContainerListener::class, $this->listener);
    }

    public function testOnRecordsModified(): void
    {
        $this->fs->touch($this->cacheDir.'/CacheClassOld.php');
        $this->listener->onRecordsModified();

        $this->assertFalse($this->fs->exists($this->cacheDir.'/CacheClassOld.php'));
    }

    public function testOnInactiveSaveCallback(): void
    {
        $this->assertSame(1, $this->listener->onInactiveSaveCallback(1));
    }

    public function testOnNameSaveCallback(): void
    {
        $dataContainer = $this->createMock(DataContainer::class);
        $dataContainer
            ->method('__get')
            ->with('activeRecord')
            ->willReturn((object) ['requestPath' => 'Bar'])
        ;

        $this->assertSame('Foo', $this->listener->onNameSaveCallback('Foo', $dataContainer));
        $this->assertSame('Bar', $this->listener->onNameSaveCallback('', $dataContainer));

        $this->inputAdapter
            ->method('post')
            ->willReturn('Baz')
        ;
        $this->assertSame('Baz', $this->listener->onNameSaveCallback('', $dataContainer));
    }

    public function testOnNameSaveCallbackThrowingException(): void
    {
        $GLOBALS['TL_LANG']['ERR']['mandatory'] = '';

        $dataContainer = $this->createMock(DataContainer::class);
        $dataContainer
            ->method('__get')
            ->withConsecutive(['activeRecord'], ['field'])
            ->willReturnOnConsecutiveCalls((object) ['requestPath' => ''], 'field')
        ;

        $this->expectException(\InvalidArgumentException::class);
        $this->listener->onNameSaveCallback('', $dataContainer);
    }

    /**
     * @dataProvider onGenerateLabelDataProvider
     */
    public function testOnGenerateLabel($provided, $expected): void
    {
        $GLOBALS['TL_LANG']['tl_url_rewrite']['priority'][0] = 'Priority';

        $this->assertSame($expected, $this->listener->onGenerateLabel($provided));
    }

    public static function onGenerateLabelDataProvider(): iterable
    {
        return [
            301 => [
                [
                    'name' => 'Foobar',
                    'requestPath' => 'foo/bar',
                    'responseUri' => 'http://domain.tld/baz/{bar}',
                    'responseCode' => 301,
                    'priority' => 0,
                ],
                'Foobar <span style="padding-left:3px;color:#b3b3b3;word-break:break-all;">[foo/bar &rarr; http://domain.tld/baz/{bar}, 301 (Priority: 0)]</span>',
            ],
            410 => [
                [
                    'name' => 'Foobar',
                    'requestPath' => 'foo/bar',
                    'responseCode' => 410,
                    'priority' => 10,
                ],
                'Foobar <span style="padding-left:3px;color:#b3b3b3;word-break:break-all;">[foo/bar &rarr; 410 (Priority: 10)]</span>',
            ],
        ];
    }

    public function testOnGenerateExamples(): void
    {
        $GLOBALS['TL_LANG'] = [
            'tl_url_rewrite' => [
                'examples' => [
                    ['foo', 'bar'],
                ],
            ],
        ];

        $buffer = $this->listener->generateExamples();

        $this->assertStringStartsWith('<div class="widget long">', $buffer);
        $this->assertStringEndsWith('</div>', $buffer);
    }

    public function testOnGetResponseCodes(): void
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

    abstract protected function getRouter();
}
