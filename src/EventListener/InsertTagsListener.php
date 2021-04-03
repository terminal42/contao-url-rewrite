<?php

declare(strict_types=1);

/*
 * UrlRewrite Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2021, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

namespace Terminal42\UrlRewriteBundle\EventListener;

use Contao\ArticleModel;
use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Environment;
use Contao\FilesModel;
use Contao\PageModel;
use Contao\Validator;

class InsertTagsListener
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * InsertTagsListener constructor.
     */
    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    /**
     * On insert tag flags.
     *
     * @param string $value
     *
     * @return string|bool
     */
    public function onInsertTagFlags(string $flag, string $tag, $value)
    {
        if ('absolute' !== $flag || 0 === stripos($value, 'http')) {
            return false;
        }

        $elements = explode('::', $tag);

        switch ($elements[0]) {
            case 'article_url':
                return $this->generateArticleUrl((string) $elements[1]);
            case 'event_url':
                return $this->generateEventUrl((string) $elements[1]);
            case 'faq_url':
                return $this->generateFaqUrl((string) $elements[1]);
            case 'file':
                return $this->generateFileUrl((string) $elements[1]);
            case 'link_url':
                return $this->generateLinkUrl((string) $elements[1]);
            case 'news_url':
                return $this->generateNewsUrl((string) $elements[1]);
        }

        return false;
    }

    /**
     * Return true if the class exists.
     */
    public function classExists(string $class): bool
    {
        return class_exists($class, false);
    }

    /**
     * Generate the article URL.
     *
     * @return bool|string
     */
    private function generateArticleUrl(string $articleId)
    {
        /** @var ArticleModel $articleAdapter */
        $articleAdapter = $this->framework->getAdapter(ArticleModel::class);

        if (null === ($article = $articleAdapter->findByIdOrAlias($articleId))
            || null === ($page = $article->getRelated('pid'))
        ) {
            return false;
        }

        /* @var PageModel $page */
        return $page->getAbsoluteUrl('/articles/'.($article->alias ?: $article->id));
    }

    /**
     * Generate the event URL.
     *
     * @return bool|string
     */
    private function generateEventUrl(string $eventId)
    {
        if (!$this->classExists('Contao\CalendarEventsModel')) {
            return false;
        }

        /** @var \Contao\CalendarEventsModel $eventAdapter */
        $eventAdapter = $this->framework->getAdapter('Contao\CalendarEventsModel');

        if (null === ($event = $eventAdapter->findByIdOrAlias($eventId))) {
            return false;
        }

        switch ($event->source) {
            case 'external':
                return $event->url;
            case 'internal':
                return $this->generateLinkUrl((string) $event->jumpTo);
            case 'article':
                return $this->generateArticleUrl((string) $event->articleId);
        }

        if (null === ($calendar = $event->getRelated('pid')) || null === ($page = $calendar->getRelated('jumpTo'))) {
            return false;
        }

        /** @var Config $config */
        $config = $this->framework->getAdapter(Config::class);

        /* @var PageModel $page */
        return $page->getAbsoluteUrl(($config->get('useAutoItem') ? '/' : '/events/').($event->alias ?: $event->id));
    }

    /**
     * Generate the FAQ URL.
     *
     * @return bool|string
     */
    private function generateFaqUrl(string $faqId)
    {
        if (!$this->classExists('Contao\FaqModel')) {
            return false;
        }

        /** @var \Contao\FaqModel $faqAdapter */
        $faqAdapter = $this->framework->getAdapter('Contao\FaqModel');

        if (null === ($faq = $faqAdapter->findByIdOrAlias($faqId))
            || null === ($category = $faq->getRelated('pid'))
            || null === ($page = $category->getRelated('jumpTo'))
        ) {
            return false;
        }

        /** @var Config $config */
        $config = $this->framework->getAdapter(Config::class);

        /* @var PageModel $page */
        return $page->getAbsoluteUrl(($config->get('useAutoItem') ? '/' : '/items/').($faq->alias ?: $faq->id));
    }

    /**
     * Generate the file URL.
     *
     * @throws \RuntimeException
     *
     * @return bool|string
     */
    private function generateFileUrl(string $file)
    {
        if (Validator::isUuid($file)) {
            /** @var FilesModel $fileAdapter */
            $fileAdapter = $this->framework->getAdapter(FilesModel::class);

            if (null !== ($model = $fileAdapter->findByUuid($file))) {
                $file = $model->path;
            }
        }

        if (Validator::isInsecurePath($file)) {
            throw new \RuntimeException('Invalid path '.$file);
        }

        return $this->framework->getAdapter(Environment::class)->get('base').ltrim($file, '/');
    }

    /**
     * Generate the link URL.
     *
     * @return bool|string
     */
    private function generateLinkUrl(string $pageId)
    {
        /** @var PageModel $pageAdapter */
        $pageAdapter = $this->framework->getAdapter(PageModel::class);

        if (null === ($page = $pageAdapter->findByIdOrAlias($pageId))) {
            return false;
        }

        switch ($page->type) {
            case 'redirect':
                return $page->url;
            case 'forward':
                if ($page->jumpTo) {
                    /** @var PageModel $next */
                    $next = $page->getRelated('jumpTo');
                } else {
                    $next = $pageAdapter->findFirstPublishedRegularByPid($page->id);
                }

                if (null !== $next) {
                    return $next->getAbsoluteUrl();
                }
        }

        return $page->getAbsoluteUrl();
    }

    /**
     * Generate the news URL.
     *
     * @return bool|string
     */
    private function generateNewsUrl(string $newsId)
    {
        if (!$this->classExists('Contao\NewsModel')) {
            return false;
        }

        /** @var \Contao\NewsModel $newsAdapter */
        $newsAdapter = $this->framework->getAdapter('Contao\NewsModel');

        if (null === ($news = $newsAdapter->findByIdOrAlias($newsId))) {
            return false;
        }

        switch ($news->source) {
            case 'external':
                return $news->url;
            case 'internal':
                return $this->generateLinkUrl((string) $news->jumpTo);
            case 'article':
                return $this->generateArticleUrl((string) $news->articleId);
        }

        if (null === ($archive = $news->getRelated('pid')) || null === ($page = $archive->getRelated('jumpTo'))) {
            return false;
        }

        /** @var Config $config */
        $config = $this->framework->getAdapter(Config::class);

        /* @var PageModel $page */
        return $page->getAbsoluteUrl(($config->get('useAutoItem') ? '/' : '/items/').($news->alias ?: $news->id));
    }
}
