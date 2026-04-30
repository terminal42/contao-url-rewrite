<?php

declare(strict_types=1);

namespace Terminal42\UrlRewriteBundle\Tests\EventListener;

use Contao\CoreBundle\Framework\Adapter;
use Contao\DataContainer;
use Contao\Input;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\RouterInterface;
use Terminal42\UrlRewriteBundle\EventListener\RewriteContainerListener;
use Terminal42\UrlRewriteBundle\QrCodeGenerator;

abstract class AbstractContainerListenerTestCase extends ContaoTestCase
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
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var Input|Adapter<Input>|MockObject
     */
    private $inputAdapter;

    protected function setUp(): void
    {
        $this->cacheDir = __DIR__.'/tmp';

        $this->fs = new Filesystem();
        $this->fs->mkdir($this->cacheDir);

        $this->router = $this->getRouter();

        $this->inputAdapter = $this->createAdapterStub(['post']);
        $framework = $this->createContaoFrameworkStub([Input::class => $this->inputAdapter]);

        $this->listener = new RewriteContainerListener(
            $this->createStub(QrCodeGenerator::class),
            $this->router,
            $this->cacheDir,
            $framework,
            $this->createStub(ExpressionFunctionProviderInterface::class),
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->fs->remove($this->cacheDir);
    }

    public function testInstantiation(): void
    {
        $this->assertInstanceOf(RewriteContainerListener::class, $this->listener);
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
        $matcher = $this->exactly(2);
        $dataContainer
            ->expects($matcher)
            ->method('__get')
            ->willReturnCallback(
                function (...$parameters) use ($matcher) {
                    if (1 === $matcher->numberOfInvocations()) {
                        $this->assertSame('activeRecord', $parameters[0]);

                        return (object) ['requestPath' => ''];
                    }
                    if (2 === $matcher->numberOfInvocations()) {
                        $this->assertSame('field', $parameters[0]);

                        return 'field';
                    }
                },
            )
        ;

        $this->expectException(\InvalidArgumentException::class);
        $this->listener->onNameSaveCallback('', $dataContainer);
    }

    /**
     * @param array<string, mixed> $provided
     */
    #[DataProvider('onGenerateLabelDataProvider')]
    public function testOnGenerateLabel(array $provided, string $expected): void
    {
        $GLOBALS['TL_LANG']['tl_url_rewrite']['priority'][0] = 'Priority';

        $this->assertSame($expected, $this->listener->onGenerateLabel($provided));
    }

    /**
     * @return iterable<int, array{0: array<string, mixed>, 1:string}>
     */
    public static function onGenerateLabelDataProvider(): iterable
    {
        yield 301 => [
            [
                'name' => 'Foobar',
                'requestPath' => 'foo/bar',
                'responseUri' => 'http://domain.tld/baz/{bar}',
                'responseCode' => 301,
                'priority' => 0,
            ],
            'Foobar <span style="padding-left:3px;color:#b3b3b3;word-break:break-all;">[foo/bar &rarr; http://domain.tld/baz/{bar}, 301 (Priority: 0)]</span>',
        ];

        yield 410 => [
            [
                'name' => 'Foobar',
                'requestPath' => 'foo/bar',
                'responseCode' => 410,
                'priority' => 10,
            ],
            'Foobar <span style="padding-left:3px;color:#b3b3b3;word-break:break-all;">[foo/bar &rarr; 410 (Priority: 10)]</span>',
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

    abstract protected function getRouter(): RouterInterface;
}
