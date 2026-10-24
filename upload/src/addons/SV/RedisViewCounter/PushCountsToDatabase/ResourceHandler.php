<?php

declare(strict_types=1);

namespace SV\RedisViewCounter\PushCountsToDatabase;

use SV\StandardLib\Helper;
use XF\Db\AbstractAdapter;

class ResourceHandler extends AbstractHandler
{
    public const CACHE_KEY = 'xfrm_resource';

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function isActive(): bool
    {
        return Helper::isAddOnActive('XFRM');
    }

    protected function getEntityContentType(): ?string
    {
        return 'resource';
    }

    protected function getViewMetricContainerDetails(AbstractAdapter $db, array $ids): array
    {
        return $db->fetchAllNum('
            SELECT resource_id, resource_date, resource_category_id 
            FROM xf_rm_resource
            WHERE resource_id in (' . $db->quote($ids) . ')
        ');
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