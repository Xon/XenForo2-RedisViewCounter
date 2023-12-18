<?php

namespace SV\RedisViewCounter\XF\Repository;

use SV\RedisViewCounter\Repository\ContentView;

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
        if (ContentView::get()->batchUpdateViews('thread', 'xf_thread', 'thread_id', 'view_count'))
        {
            return;
        }
        parent::batchUpdateThreadViews();
    }
}