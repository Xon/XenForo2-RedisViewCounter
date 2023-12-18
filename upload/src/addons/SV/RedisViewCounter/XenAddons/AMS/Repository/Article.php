<?php

namespace SV\RedisViewCounter\XenAddons\AMS\Repository;

use SV\RedisViewCounter\Repository\ContentView;
use XenAddons\AMS\Entity\ArticleItem;

/**
 * @extends \XenAddons\AMS\Repository\Article
 */
class Article extends XFCP_Article
{
    public function logArticleView(ArticleItem $articleItem)
    {
        if (ContentView::get()->logView('ams_article', $articleItem->article_id))
        {
            return;
        }
        parent::logArticleView($articleItem);
    }

    public function batchUpdateArticleViews()
    {
        if (ContentView::get()->batchUpdateViews('ams_article', 'xf_xa_ams_article', 'article_id', 'view_count'))
        {
            return;
        }
        parent::batchUpdateArticleViews();
    }
}