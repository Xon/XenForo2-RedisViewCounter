<?php

declare(strict_types=1);

namespace SV\RedisViewCounter\PushCountsToDatabase;

use XF\Db\AbstractAdapter;

class AttachmentHandler extends AbstractHandler
{
    public const CACHE_KEY = 'attachment';

    protected function getEntityContentType(): ?string
    {
        return null;
    }

    protected function getViewMetricContainerDetails(AbstractAdapter $db, array $ids): array
    {
        return [];
    }

    protected function updateViewTotals(array $viewMetrics): void
    {
        $db = \XF::db();
        foreach ($viewMetrics as $id => $viewCount)
        {
            $db->query('UPDATE xf_attachment SET view_count = view_count + ? WHERE attachment_id = ?', [$viewCount, $id]);
        }
    }
}