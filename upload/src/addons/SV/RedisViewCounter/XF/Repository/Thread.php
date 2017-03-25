<?php

namespace SV\RedisViewCounter\XF\Repository;

class Thread extends XFCP_Thread
{
    public function logThreadView(\XF\Entity\Thread $thread)
    {
        /** @var \SV\RedisViewCounter\Repository\ContentView $creator */
        $contentView = $this->repository('\SV\RedisViewCounter\Repository\ContentView');
        if ($contentView->logView('thread', $thread->thread_id))
        {
            return;
        }
        parent::logThreadView($thread);
    }

    public function batchUpdateThreadViews()
    {
        /** @var \SV\RedisViewCounter\Repository\ContentView $creator */
        $contentView = $this->repository('\SV\RedisViewCounter\Repository\ContentView');
        if ($contentView->batchUpdateViews('thread', 'xf_thread', 'thread_id', 'view_count'))
        {
            return;
        }
        parent::batchUpdateThreadViews($attachment);
    }
}