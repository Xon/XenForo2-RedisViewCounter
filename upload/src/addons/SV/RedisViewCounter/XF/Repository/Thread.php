<?php

declare(strict_types=1);

namespace SV\RedisViewCounter\XF\Repository;

use SV\RedisViewCounter\PushCountsToDatabase\ThreadHandler;
use SV\RedisViewCounter\Repository\ContentView;
use SV\RedisViewCounter\XF\Repository\Page as ExtendedPageRepo;
use XF\Repository\Page as PageRepo;
use SV\StandardLib\Helper;

/**
 * @extends \XF\Repository\Thread
 */
class Thread extends XFCP_Thread
{
    public function logThreadView(\XF\Entity\Thread $thread)
    {
        if (ContentView::get()->logView(ThreadHandler::CACHE_KEY, $thread->thread_id))
        {
            return;
        }
        parent::logThreadView($thread);
    }

    public function batchUpdateThreadViews()
    {
        /** @var ExtendedPageRepo $pageRepo */
        $pageRepo = Helper::repository(PageRepo::class);
        $pageRepo->batchUpdateViews();

        if (ContentView::get()->batchUpdateViews(ThreadHandler::CACHE_KEY))
        {
            return;
        }
        parent::batchUpdateThreadViews();
    }
}