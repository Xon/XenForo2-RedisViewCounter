<?php

namespace SV\RedisViewCounter\XF\Repository;

use SV\RedisViewCounter\Repository\ContentView;

class Thread extends XFCP_Thread
{
    public function logThreadView(\XF\Entity\Thread $thread)
    {
        /** @var ContentView $contentView */
        $contentView = $this->repository('SV\RedisViewCounter:ContentView');
        if ($contentView->logView('thread', $thread->thread_id))
        {
            return;
        }
        parent::logThreadView($thread);
    }

    public function batchUpdateThreadViews()
    {
        /** @var ContentView $contentView */
        $contentView = $this->repository('SV\RedisViewCounter:ContentView');
        if ($contentView->batchUpdateViews('thread', 'xf_thread', 'thread_id', 'view_count'))
        {
            return;
        }
        parent::batchUpdateThreadViews();
    }
}