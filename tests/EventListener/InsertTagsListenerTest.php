<?php

declare(strict_types=1);

namespace Terminal42\UrlRewriteBundle\Tests\EventListener;

use Contao\ArticleModel;
use Contao\Config;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Environment;
use Contao\FilesModel;
use Contao\PageModel;
use PHPUnit\Framework\TestCase;
use Terminal42\UrlRewriteBundle\EventListener\InsertTagsListener;

class InsertTagsListenerTest extends TestCase
{
    public function testInstantiation(): void
    {
        $this->assertInstanceOf(InsertTagsListener::class, new InsertTagsListener($this->createFrameworkMock()));
    }

    public function testClassExists(): void
    {
        $listener = new InsertTagsListener($this->createFrameworkMock());

        $this->assertFalse($listener->classExists('foobar'));
        $this->assertTrue($listener->classExists(InsertTagsListener::class));
    }

    public function testOnInsertFlagsInvalid(): void
    {
        $listener = new InsertTagsListener($this->createFrameworkMock());

        $this->assertFalse($listener->onInsertTagFlags('foobar', '', ''));
        $this->assertFalse($listener->onInsertTagFlags('absolute', '', 'http://domain.tld'));
        $this->assertFalse($listener->onInsertTagFlags('absolute', 'foobar', 'domain.tld'));
    }

    /**
     * @dataProvider articleUrlDataProvider
     */
    public function testOnInsertFlagsArticleUrl($provided, $expected): void
    {
        $listener = new InsertTagsListener($this->createFrameworkMock($provided));

        $this->assertSame($expected, $listener->onInsertTagFlags('absolute', 'article_url::1', ''));
    }

    public function articleUrlDataProvider()
    {
        $article1 = $this->createAdapterMock(['findByIdOrAlias']);
        $article1
            ->method('findByIdOrAlias')
            ->willReturn(null)
        ;

        $article2 = $this->createAdapterMock(['findByIdOrAlias', 'getRelated']);
        $article2
            ->method('findByIdOrAlias')
            ->willReturn($article2)
        ;
        $article2
            ->method('getRelated')
            ->willReturn(null)
        ;

        $page = $this->createAdapterMock(['getAbsoluteUrl']);
        $page
            ->method('getAbsoluteUrl')
            ->willReturnCallback(function ($buffer) {
                return sprintf('http://domain.tld/page%s.html', $buffer);
            })
        ;

        $article3 = $this->createAdapterMock(['findByIdOrAlias', 'getRelated']);
        $article3->alias = 'foobar';
        $article3
            ->method('findByIdOrAlias')
            ->willReturn($article3)
        ;
        $article3
            ->method('getRelated')
            ->willReturn($page)
        ;

        return [
            'No article' => [
                [ArticleModel::class => $article1],
                false,
            ],
            'No page' => [
                [ArticleModel::class => $article2],
                false,
            ],
            'Correct' => [
                [ArticleModel::class => $article3],
                'http://domain.tld/page/articles/foobar.html',
            ],
        ];
    }

    public function testOnInsertFlagsFileUrlInsecurePath(): void
    {
        $this->expectException(\RuntimeException::class);

        $listener = new InsertTagsListener($this->createFrameworkMock());
        $listener->onInsertTagFlags('absolute', 'file::foo/../bar', '');
    }

    public function testOnInsertFlagsFileUrl(): void
    {
        $model = $this->createAdapterMock();
        $model->path = '/foobar.zip';

        $file = $this->createAdapterMock(['findByUuid']);
        $file
            ->method('findByUuid')
            ->willReturn($model)
        ;

        $environment = $this->createAdapterMock(['get']);
        $environment
            ->method('get')
            ->willReturn('http://domain.tld/')
        ;

        $framework = $this->createFrameworkMock([
            FilesModel::class => $file,
            Environment::class => $environment,
        ]);

        $listener = new InsertTagsListener($framework);

        $this->assertSame('http://domain.tld/foobar.zip', $listener->onInsertTagFlags('absolute', 'file::632cce39-cea3-11e6-87f4-ac87a32709d5', ''));
    }

    /**
     * @dataProvider linkUrlDataProvider
     */
    public function testOnInsertFlagsLinkUrl($provided, $expected): void
    {
        $listener = new InsertTagsListener($this->createFrameworkMock($provided));

        $this->assertSame($expected, $listener->onInsertTagFlags('absolute', 'link_url::1', ''));
    }

    public function linkUrlDataProvider()
    {
        // No page
        $pageAdapter1 = $this->createAdapterMock(['findByIdOrAlias']);
        $pageAdapter1
            ->method('findByIdOrAlias')
            ->willReturn(null)
        ;

        // Redirect page
        $page2 = $this->createAdapterMock();
        $page2->type = 'redirect';
        $page2->url = 'http://domain.tld/redirect.html';

        $pageAdapter2 = $this->createAdapterMock(['findByIdOrAlias']);
        $pageAdapter2
            ->method('findByIdOrAlias')
            ->willReturn($page2)
        ;

        // Forward page 1
        $page3a = $this->createAdapterMock(['getAbsoluteUrl']);
        $page3a
            ->method('getAbsoluteUrl')
            ->willReturn('http://domain.tld/forward1.html')
        ;

        $page3b = $this->createAdapterMock(['getRelated']);
        $page3b->type = 'forward';
        $page3b->jumpTo = 1;
        $page3b
            ->method('getRelated')
            ->willReturn($page3a)
        ;

        $pageAdapter3 = $this->createAdapterMock(['findByIdOrAlias']);
        $pageAdapter3
            ->method('findByIdOrAlias')
            ->willReturn($page3b)
        ;

        // Forward page 2
        $page4a = $this->createAdapterMock(['getAbsoluteURl']);
        $page4a
            ->method('getAbsoluteUrl')
            ->willReturn('http://domain.tld/forward2.html')
        ;

        $page4b = $this->createAdapterMock();
        $page4b->id = 1;
        $page4b->type = 'forward';
        $page4b->jumpTo = 0;

        $pageAdapter4 = $this->createAdapterMock(['findByIdOrAlias', 'findFirstPublishedRegularByPid']);
        $pageAdapter4
            ->method('findByIdOrAlias')
            ->willReturn($page4b)
        ;
        $pageAdapter4
            ->method('findFirstPublishedRegularByPid')
            ->willReturn($page4a)
        ;

        // Regular page
        $page5 = $this->createAdapterMock(['getAbsoluteUrl']);
        $page5->type = 'regular';
        $page5
            ->method('getAbsoluteUrl')
            ->willReturn('http://domain.tld/regular.html')
        ;

        $pageAdapter5 = $this->createAdapterMock(['findByIdOrAlias']);
        $pageAdapter5
            ->method('findByIdOrAlias')
            ->willReturn($page5)
        ;

        return [
            'No page' => [
                [PageModel::class => $pageAdapter1],
                false,
            ],
            'Redirect page' => [
                [PageModel::class => $pageAdapter2],
                'http://domain.tld/redirect.html',
            ],
            'Forward page 1' => [
                [PageModel::class => $pageAdapter3],
                'http://domain.tld/forward1.html',
            ],
            'Forward page 2' => [
                [PageModel::class => $pageAdapter4],
                'http://domain.tld/forward2.html',
            ],
            'Regular page' => [
                [PageModel::class => $pageAdapter5],
                'http://domain.tld/regular.html',
            ],
        ];
    }

    /**
     * @dataProvider eventUrlDataProvider
     */
    public function testOnInsertFlagsEventUrl($provided, $expected): void
    {
        $listener = $this
            ->getMockBuilder(InsertTagsListener::class)
            ->setConstructorArgs([$this->createFrameworkMock($provided[0])])
            ->onlyMethods(['classExists'])
            ->getMock()
        ;

        $listener
            ->method('classExists')
            ->willReturn($provided[1])
        ;

        $this->assertSame($expected, $listener->onInsertTagFlags('absolute', 'event_url::1', ''));
    }

    public function eventUrlDataProvider()
    {
        // No event
        $eventAdapter1 = $this->createAdapterMock(['findByIdOrAlias']);
        $eventAdapter1
            ->method('findByIdOrAlias')
            ->willReturn(null)
        ;

        // External event
        $event2 = $this->createAdapterMock();
        $event2->source = 'external';
        $event2->url = 'http://external.tld';

        $eventAdapter2 = $this->createAdapterMock(['findByIdOrAlias']);
        $eventAdapter2
            ->method('findByIdOrAlias')
            ->willReturn($event2)
        ;

        // Internal event
        $event3 = $this->createAdapterMock();
        $event3->source = 'internal';
        $event3->jumpTo = 1;

        $eventAdapter3 = $this->createAdapterMock(['findByIdOrAlias']);
        $eventAdapter3
            ->method('findByIdOrAlias')
            ->willReturn($event3)
        ;

        $page3 = $this->createAdapterMock(['getAbsoluteUrl']);
        $page3->type = '';
        $page3
            ->method('getAbsoluteUrl')
            ->willReturn('http://domain.tld/internal.html')
        ;

        $pageAdapter3 = $this->createAdapterMock(['findByIdOrAlias']);
        $pageAdapter3
            ->method('findByIdOrAlias')
            ->willReturn($page3)
        ;

        // Article event
        $event4 = $this->createAdapterMock();
        $event4->source = 'article';
        $event4->articleId = 1;

        $eventAdapter4 = $this->createAdapterMock(['findByIdOrAlias']);
        $eventAdapter4
            ->method('findByIdOrAlias')
            ->willReturn($event4)
        ;

        $page4 = $this->createAdapterMock(['getAbsoluteUrl']);
        $page4
            ->method('getAbsoluteUrl')
            ->willReturn('http://domain.tld/articles/foobar.html')
        ;

        $article4 = $this->createAdapterMock(['getRelated']);
        $article4->alias = 'foobar';
        $article4
            ->method('getRelated')
            ->willReturn($page4)
        ;

        $articleAdapter4 = $this->createAdapterMock(['findByIdOrAlias']);
        $articleAdapter4
            ->method('findByIdOrAlias')
            ->willReturn($article4)
        ;

        // Regular event
        $page5 = $this->createAdapterMock(['getAbsoluteUrl']);
        $page5
            ->method('getAbsoluteUrl')
            ->willReturn('http://domain.tld/events/foobar.html')
        ;

        $calendar5 = $this->createAdapterMock(['getRelated']);
        $calendar5
            ->method('getRelated')
            ->willReturn($page5)
        ;

        $event5 = $this->createAdapterMock(['getRelated']);
        $event5->source = '';
        $event5->alias = 'foobar';
        $event5
            ->method('getRelated')
            ->willReturn($calendar5)
        ;

        $eventAdapter5 = $this->createAdapterMock(['findByIdOrAlias']);
        $eventAdapter5
            ->method('findByIdOrAlias')
            ->willReturn($event5)
        ;

        // No calendar
        $event6 = $this->createAdapterMock(['getRelated']);
        $event6->source = '';
        $event6
            ->method('getRelated')
            ->willReturn(null)
        ;

        $eventAdapter6 = $this->createAdapterMock(['findByIdOrAlias']);
        $eventAdapter6
            ->method('findByIdOrAlias')
            ->willReturn($event6)
        ;

        return [
            'Class does not exist' => [
                [[], false],
                false,
            ],
            'No event' => [
                [['Contao\CalendarEventsModel' => $eventAdapter1], true],
                false,
            ],
            'External event' => [
                [['Contao\CalendarEventsModel' => $eventAdapter2], true],
                'http://external.tld',
            ],
            'Internal event' => [
                [
                    [
                        'Contao\CalendarEventsModel' => $eventAdapter3,
                        PageModel::class => $pageAdapter3,
                    ],
                    true,
                ],
                'http://domain.tld/internal.html',
            ],
            'Article event' => [
                [
                    [
                        'Contao\CalendarEventsModel' => $eventAdapter4,
                        ArticleModel::class => $articleAdapter4,
                    ],
                    true,
                ],
                'http://domain.tld/articles/foobar.html',
            ],
            'Regular event' => [
                [
                    [
                        'Contao\CalendarEventsModel' => $eventAdapter5,
                        Config::class => $this->createAdapterMock(['get']),
                    ],
                    true,
                ],
                'http://domain.tld/events/foobar.html',
            ],
            'No calendar' => [
                [['Contao\CalendarEventsModel' => $eventAdapter6], true],
                false,
            ],
        ];
    }

    /**
     * @dataProvider faqUrlDataProvider
     */
    public function testOnInsertFlagsFaqUrl($provided, $expected): void
    {
        $listener = $this
            ->getMockBuilder(InsertTagsListener::class)
            ->setConstructorArgs([$this->createFrameworkMock($provided[0])])
            ->onlyMethods(['classExists'])
            ->getMock()
        ;

        $listener
            ->method('classExists')
            ->willReturn($provided[1])
        ;

        $this->assertSame($expected, $listener->onInsertTagFlags('absolute', 'faq_url::1', ''));
    }

    public function faqUrlDataProvider()
    {
        // No faq
        $faqAdapter1 = $this->createAdapterMock(['findByIdOrAlias']);
        $faqAdapter1
            ->method('findByIdOrAlias')
            ->willReturn(null)
        ;

        // No category
        $faq2 = $this->createAdapterMock(['getRelated']);
        $faq2
            ->method('getRelated')
            ->willReturn(null)
        ;

        $faqAdapter2 = $this->createAdapterMock(['findByIdOrAlias']);
        $faqAdapter2
            ->method('findByIdOrAlias')
            ->willReturn($faq2)
        ;

        // No page
        $category3 = $this->createAdapterMock(['getRelated']);
        $category3
            ->method('getRelated')
            ->willReturn(null)
        ;

        $faq3 = $this->createAdapterMock(['getRelated']);
        $faq3
            ->method('getRelated')
            ->willReturn($category3)
        ;

        $faqAdapter3 = $this->createAdapterMock(['findByIdOrAlias']);
        $faqAdapter3
            ->method('findByIdOrAlias')
            ->willReturn($faq3)
        ;

        // Regular faq
        $page4 = $this->createAdapterMock(['getAbsoluteUrl']);
        $page4
            ->method('getAbsoluteUrl')
            ->willReturn('http://domain.tld/items/faq.html')
        ;

        $category4 = $this->createAdapterMock(['getRelated']);
        $category4
            ->method('getRelated')
            ->willReturn($page4)
        ;

        $faq4 = $this->createAdapterMock(['getRelated']);
        $faq4->alias = 'foobar';
        $faq4
            ->method('getRelated')
            ->willReturn($category4)
        ;

        $faqAdapter4 = $this->createAdapterMock(['findByIdOrAlias']);
        $faqAdapter4
            ->method('findByIdOrAlias')
            ->willReturn($faq4)
        ;

        return [
            'Class does not exist' => [
                [[], false],
                false,
            ],
            'No faq' => [
                [['Contao\FaqModel' => $faqAdapter1], true],
                false,
            ],
            'No category' => [
                [['Contao\FaqModel' => $faqAdapter2], true],
                false,
            ],
            'No page' => [
                [['Contao\FaqModel' => $faqAdapter3], true],
                false,
            ],
            'Regular faq' => [
                [
                    [
                        'Contao\FaqModel' => $faqAdapter4,
                        Config::class => $this->createAdapterMock(['get']),
                    ],
                    true,
                ],
                'http://domain.tld/items/faq.html',
            ],
        ];
    }

    /**
     * @dataProvider newsUrlDataProvider
     */
    public function testOnInsertFlagsNewsUrl($provided, $expected): void
    {
        $listener = $this
            ->getMockBuilder(InsertTagsListener::class)
            ->setConstructorArgs([$this->createFrameworkMock($provided[0])])
            ->onlyMethods(['classExists'])
            ->getMock()
        ;

        $listener
            ->method('classExists')
            ->willReturn($provided[1])
        ;

        $this->assertSame($expected, $listener->onInsertTagFlags('absolute', 'news_url::1', ''));
    }

    public function newsUrlDataProvider()
    {
        // No news
        $newsAdapter1 = $this->createAdapterMock(['findByIdOrAlias']);
        $newsAdapter1
            ->method('findByIdOrAlias')
            ->willReturn(null)
        ;

        // External news
        $news2 = $this->createAdapterMock();
        $news2->source = 'external';
        $news2->url = 'http://external.tld';

        $newsAdapter2 = $this->createAdapterMock(['findByIdOrAlias']);
        $newsAdapter2
            ->method('findByIdOrAlias')
            ->willReturn($news2)
        ;

        // Internal news
        $news3 = $this->createAdapterMock();
        $news3->source = 'internal';
        $news3->jumpTo = 1;

        $newsAdapter3 = $this->createAdapterMock(['findByIdOrAlias']);
        $newsAdapter3
            ->method('findByIdOrAlias')
            ->willReturn($news3)
        ;

        $page3 = $this->createAdapterMock(['getAbsoluteUrl']);
        $page3->type = '';
        $page3
            ->method('getAbsoluteUrl')
            ->willReturn('http://domain.tld/internal.html')
        ;

        $pageAdapter3 = $this->createAdapterMock(['findByIdOrAlias']);
        $pageAdapter3
            ->method('findByIdOrAlias')
            ->willReturn($page3)
        ;

        // Article news
        $news4 = $this->createAdapterMock();
        $news4->source = 'article';
        $news4->articleId = 1;

        $newsAdapter4 = $this->createAdapterMock(['findByIdOrAlias']);
        $newsAdapter4
            ->method('findByIdOrAlias')
            ->willReturn($news4)
        ;

        $page4 = $this->createAdapterMock(['getAbsoluteUrl']);
        $page4
            ->method('getAbsoluteUrl')
            ->willReturn('http://domain.tld/articles/foobar.html')
        ;

        $article4 = $this->createAdapterMock(['getRelated']);
        $article4->alias = 'foobar';
        $article4
            ->method('getRelated')
            ->willReturn($page4)
        ;

        $articleAdapter4 = $this->createAdapterMock(['findByIdOrAlias']);
        $articleAdapter4
            ->method('findByIdOrAlias')
            ->willReturn($article4)
        ;

        // Regular news
        $page5 = $this->createAdapterMock(['getAbsoluteUrl']);
        $page5
            ->method('getAbsoluteUrl')
            ->willReturn('http://domain.tld/newss/foobar.html')
        ;

        $archive5 = $this->createAdapterMock(['getRelated']);
        $archive5
            ->method('getRelated')
            ->willReturn($page5)
        ;

        $news5 = $this->createAdapterMock(['getRelated']);
        $news5->source = '';
        $news5->alias = 'foobar';
        $news5
            ->method('getRelated')
            ->willReturn($archive5)
        ;

        $newsAdapter5 = $this->createAdapterMock(['findByIdOrAlias']);
        $newsAdapter5
            ->method('findByIdOrAlias')
            ->willReturn($news5)
        ;

        // No calendar
        $news6 = $this->createAdapterMock(['getRelated']);
        $news6->source = '';
        $news6
            ->method('getRelated')
            ->willReturn(null)
        ;

        $newsAdapter6 = $this->createAdapterMock(['findByIdOrAlias']);
        $newsAdapter6
            ->method('findByIdOrAlias')
            ->willReturn($news6)
        ;

        return [
            'Class does not exist' => [
                [[], false],
                false,
            ],
            'No news' => [
                [['Contao\NewsModel' => $newsAdapter1], true],
                false,
            ],
            'External news' => [
                [['Contao\NewsModel' => $newsAdapter2], true],
                'http://external.tld',
            ],
            'Internal news' => [
                [
                    [
                        'Contao\NewsModel' => $newsAdapter3,
                        PageModel::class => $pageAdapter3,
                    ],
                    true,
                ],
                'http://domain.tld/internal.html',
            ],
            'Article news' => [
                [
                    [
                        'Contao\NewsModel' => $newsAdapter4,
                        ArticleModel::class => $articleAdapter4,
                    ],
                    true,
                ],
                'http://domain.tld/articles/foobar.html',
            ],
            'Regular news' => [
                [
                    [
                        'Contao\NewsModel' => $newsAdapter5,
                        Config::class => $this->createAdapterMock(['get']),
                    ],
                    true,
                ],
                'http://domain.tld/newss/foobar.html',
            ],
            'No calendar' => [
                [['Contao\NewsModel' => $newsAdapter6], true],
                false,
            ],
        ];
    }

    private function createFrameworkMock(array $adapters = [])
    {
        $framework = $this->createMock(ContaoFramework::class);

        if (\count($adapters) > 0) {
            $framework
                ->method('getAdapter')
                ->willReturnCallback(
                    function ($param) use ($adapters) {
                        return $adapters[$param];
                    }
                )
            ;
        }

        return $framework;
    }

    private function createAdapterMock(array $methods = [])
    {
        return $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->addMethods($methods)
            ->getMock()
        ;
    }
}
