<?php

declare(strict_types=1);

namespace SV\RedisViewCounter\XF\Repository;

use SV\RedisViewCounter\Repository\ContentView;
use SV\RedisViewCounter\XF\Repository\Page as PageRepo;

class Thread extends XFCP_Thread
{
    public function logThreadView(\XF\Entity\Thread $thread)
    {
        if (ContentView::get()->logView('thread', $thread->thread_id))
        {
            return;
        }
        parent::logThreadView($thread);
    }

    public function batchUpdateThreadViews()
    {
        /** @var PageRepo $attachmentRepo */
        $attachmentRepo = \XF::app()->repository('XF:Page');
        $attachmentRepo->batchUpdateViews();

        if (ContentView::get()->batchUpdateViews('thread', 'xf_thread', 'thread_id', 'view_count'))
        {
            return;
        }
        parent::batchUpdateThreadViews();
    }
}