<?php

declare(strict_types=1);

namespace SV\RedisViewCounter\XF\Repository;

use SV\RedisViewCounter\PushCountsToDatabase\PageHandler;
use SV\RedisViewCounter\Repository\ContentView;
use XF\Entity\Page as PageEntity;
use XF\Entity\User as UserEntity;
use function is_callable;

/**
 * Extends \XF\Repository\Page
 */
class Page extends XFCP_Page
{
    public function logView(PageEntity $page, UserEntity $user)
    {
        if (ContentView::get()->logView(PageHandler::CACHE_KEY, $page->node_id))
        {
            return;
        }
        parent::logView($page, $user);
    }

    public function batchUpdateViews()
    {
        if (ContentView::get()->batchUpdateViews(PageHandler::CACHE_KEY))
        {
            return;
        }
        if (is_callable([parent::class, 'batchUpdateViews']))
        {
            /** @noinspection PhpUndefinedMethodInspection */
            parent::batchUpdateViews();
        }
    }
}