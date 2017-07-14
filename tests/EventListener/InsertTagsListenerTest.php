<?php

namespace Terminal42\UrlRewriteBundle\Tests\EventListener;

use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use PHPUnit\Framework\TestCase;
use Terminal42\UrlRewriteBundle\EventListener\InsertTagsListener;

class InsertTagsListenerTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(InsertTagsListener::class, new InsertTagsListener($this->createMock(ContaoFramework::class)));
    }

    public function testOnInsertFlagsInvalid()
    {
        $listener = new InsertTagsListener($this->createMock(ContaoFramework::class));

        $this->assertFalse($listener->onInsertTagFlags('foobar', '', ''));
        $this->assertFalse($listener->onInsertTagFlags('absolute', '', 'http://domain.tld'));
        $this->assertFalse($listener->onInsertTagFlags('absolute', 'foobar', 'domain.tld'));
    }

    /**
     * @dataProvider articleUrlDataProvider
     */
    public function testOnInsertFlagsArticleUrl($provided, $expected)
    {
        $framework = $this->createMock(ContaoFramework::class);

        $framework
            ->method('getAdapter')
            ->willReturn($provided);
        ;

        $listener = new InsertTagsListener($framework);

        $this->assertEquals($expected, $listener->onInsertTagFlags('absolute', 'article_url::1', ''));
    }

    public function articleUrlDataProvider()
    {
        $articleBuilder = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['findByIdOrAlias', 'getRelated'])
        ;

        $article1 = $articleBuilder->getMock();
        $article1->method('findByIdOrAlias')->willReturn(null);

        $article2 = $articleBuilder->getMock();
        $article2->method('findByIdOrAlias')->willReturn($article2);
        $article2->method('getRelated')->willReturn(null);

        $page = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAbsoluteUrl'])
            ->getMock()
        ;

        $page
            ->method('getAbsoluteUrl')
            ->willReturnCallback(function ($buffer) {
                return sprintf('http://domain.tld/page%s.html', $buffer);
            })
        ;

        $article3 = $articleBuilder->getMock();
        $article3->alias = 'foobar';
        $article3->method('findByIdOrAlias')->willReturn($article3);
        $article3->method('getRelated')->willReturn($page);

        return [
            'No article' => [
                $article1,
                false
            ],
            'No page' => [
                $article2,
                false
            ],
            'Correct' => [
                $article3,
                'http://domain.tld/page/articles/foobar.html'
            ],
        ];
    }

    public function testOnInsertFlagsFileUrlInsecurePath()
    {
        $this->expectException(\RuntimeException::class);

        $listener = new InsertTagsListener($this->createMock(ContaoFramework::class));
        $listener->onInsertTagFlags('absolute', 'file::foo/../bar', '');
    }

    public function testOnInsertFlagsFileUrl()
    {
        $file = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['findByUuid'])
            ->getMock()
        ;

        $model = new \stdClass();
        $model->path = '/foobar.zip';

        $file
            ->method('findByUuid')
            ->willReturn($model)
        ;

        $environment = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock()
        ;

        $environment
            ->method('get')
            ->willReturn('http://domain.tld/')
        ;

        $framework = $this->createMock(ContaoFramework::class);

        $framework
            ->method('getAdapter')
            ->willReturn($file, $environment)
        ;

        $listener = new InsertTagsListener($framework);

        $this->assertEquals('http://domain.tld/foobar.zip', $listener->onInsertTagFlags('absolute', 'file::632cce39-cea3-11e6-87f4-ac87a32709d5', ''));
    }

    /**
     * @dataProvider linkUrlDataProvider
     */
    public function testOnInsertFlagsLinkUrl($provided, $expected)
    {
        $framework = $this->createMock(ContaoFramework::class);

        $framework
            ->method('getAdapter')
            ->willReturn($provided);
        ;

        $listener = new InsertTagsListener($framework);

        $this->assertEquals($expected, $listener->onInsertTagFlags('absolute', 'link_url::1', ''));
    }

    public function linkUrlDataProvider()
    {
        $pageAdapter = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['findByIdOrAlias', 'findFirstPublishedRegularByPid'])
        ;

        $page = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRelated', 'getAbsoluteUrl'])
        ;

        // No page
        $pageAdapter1 = $pageAdapter->getMock();
        $pageAdapter1->method('findByIdOrAlias')->willReturn(null);

        // Redirect page
        $page2 = $page->getMock();
        $page2->type = 'redirect';
        $page2->url = 'http://domain.tld/redirect.html';

        $pageAdapter2 = $pageAdapter->getMock();
        $pageAdapter2->method('findByIdOrAlias')->willReturn($page2);

        // Forward page 1
        $page3a = $page->getMock();
        $page3a->method('getAbsoluteUrl')->willReturn('http://domain.tld/forward1.html');

        $page3b = $page->getMock();
        $page3b->type = 'forward';
        $page3b->jumpTo = 1;
        $page3b->method('getRelated')->willReturn($page3a);

        $pageAdapter3 = $pageAdapter->getMock();
        $pageAdapter3->method('findByIdOrAlias')->willReturn($page3b);

        // Forward page 2
        $page4a = $page->getMock();
        $page4a->method('getAbsoluteUrl')->willReturn('http://domain.tld/forward2.html');

        $page4b = $page->getMock();
        $page4b->id = 1;
        $page4b->type = 'forward';
        $page4b->jumpTo = 0;

        $pageAdapter4 = $pageAdapter->getMock();
        $pageAdapter4->method('findByIdOrAlias')->willReturn($page4b);
        $pageAdapter4->method('findFirstPublishedRegularByPid')->willReturn($page4a);

        // Regular page
        $page5 = $page->getMock();
        $page5->type = 'regular';
        $page5->method('getAbsoluteUrl')->willReturn('http://domain.tld/regular.html');

        $pageAdapter5 = $pageAdapter->getMock();
        $pageAdapter5->method('findByIdOrAlias')->willReturn($page5);

        return [
            'No page' => [
                $pageAdapter1,
                false
            ],
            'Redirect page' => [
                $pageAdapter2,
                'http://domain.tld/redirect.html'
            ],
            'Forward page 1' => [
                $pageAdapter3,
                'http://domain.tld/forward1.html'
            ],
            'Forward page 2' => [
                $pageAdapter4,
                'http://domain.tld/forward2.html'
            ],
            'Regular page' => [
                $pageAdapter5,
                'http://domain.tld/regular.html'
            ],
        ];
    }
}
