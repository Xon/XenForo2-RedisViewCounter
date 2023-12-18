<?php

namespace SV\RedisViewCounter\XF\Repository;

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
        if (ContentView::get()->logView('page', $page->node_id))
        {
            return;
        }
        parent::logView($page, $user);
    }

    public function batchUpdateViews()
    {
        if (ContentView::get()->batchUpdateViews('page', 'xf_page', 'node_id', 'view_count'))
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