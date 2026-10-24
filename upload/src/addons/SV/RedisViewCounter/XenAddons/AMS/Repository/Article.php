<?php

declare(strict_types=1);

namespace SV\RedisViewCounter\XenAddons\AMS\Repository;

use SV\RedisViewCounter\PushCountsToDatabase\AMSArticleHandler;
use SV\RedisViewCounter\Repository\ContentView;
use XenAddons\AMS\Entity\ArticleItem;

/**
 * @extends \XenAddons\AMS\Repository\Article
 */
class Article extends XFCP_Article
{
    public function logArticleView(ArticleItem $article)
    {
        if (ContentView::get()->logView(AMSArticleHandler::CACHE_KEY, $article->article_id))
        {
            return;
        }
        parent::logArticleView($article);
    }

    public function batchUpdateArticleViews()
    {
        if (ContentView::get()->batchUpdateViews(AMSArticleHandler::CACHE_KEY))
        {
            return;
        }
        parent::batchUpdateArticleViews();
    }
}