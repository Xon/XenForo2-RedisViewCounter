<?php

declare(strict_types=1);

namespace SV\RedisViewCounter\PushCountsToDatabase;

use SV\StandardLib\Helper;
use XF\Db\AbstractAdapter;

class PageHandler extends AbstractHandler
{
    public const CACHE_KEY = 'page';

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function isActive(): bool
    {
        return Helper::isAddOnActive('SV/BbCodePages');
    }

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
            $db->query('UPDATE xf_page SET view_count = view_count + ? WHERE node_id = ?', [$viewCount, $id]);
        }
    }
}