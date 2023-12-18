<?php

namespace SV\RedisViewCounter\XF\Repository;

use SV\RedisViewCounter\Repository\ContentView;
use XF\Entity\Page as PageEntity;
use XF\Entity\User as UserEntity;

/**
 * Extends \XF\Repository\Page
 */
class Page extends XFCP_Page
{
    public function logView(PageEntity $page, UserEntity $user)
    {
        /** @var ContentView $contentView */
        $contentView = $this->repository('SV\RedisViewCounter:ContentView');
        if ($contentView->logView('page', $page->node_id))
        {
            return;
        }
        parent::logView($page, $user);
    }

    public function batchUpdateViews()
    {
        /** @var ContentView $contentView */
        $contentView = $this->repository('SV\RedisViewCounter:ContentView');
        if ($contentView->batchUpdateViews('page', 'xf_page', 'node_id', 'view_count'))
        {
            /** @noinspection PhpUnnecessaryStopStatementInspection */
            return;
        }
        //parent::batchUpdateViews();
    }
}