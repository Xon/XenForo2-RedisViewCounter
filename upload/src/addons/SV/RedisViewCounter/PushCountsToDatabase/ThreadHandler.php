<?php

declare(strict_types=1);

namespace SV\RedisViewCounter\PushCountsToDatabase;

use XF\Db\AbstractAdapter;

class ThreadHandler extends AbstractHandler
{
    public const CACHE_KEY = 'thread';

    protected function getEntityContentType(): ?string
    {
        return 'thread';
    }

    protected function getViewMetricContainerDetails(AbstractAdapter $db, array $ids): array
    {
        return $db->fetchAllNum('
            SELECT thread_id, post_date, node_id
            FROM xf_thread
            WHERE thread_id in (' . $db->quote($ids) . ')
        ');
    }

    protected function updateViewTotals(array $viewMetrics): void
    {
        $db = \XF::db();
        foreach ($viewMetrics as $id => $viewCount)
        {
            $db->query('UPDATE xf_thread SET view_count = view_count + ? WHERE thread_id = ?', [$viewCount, $id]);
        }
    }
}