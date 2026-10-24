<?php

declare(strict_types=1);

namespace SV\RedisViewCounter\PushCountsToDatabase;

use SV\StandardLib\Helper;
use XF\Db\AbstractAdapter;

class AMSArticleHandler extends AbstractHandler
{
    public const CACHE_KEY = 'ams_article';

    protected function getEntityContentType(): ?string
    {
        return 'ams_article';
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function isActive(): bool
    {
        return Helper::isAddOnActive('XenAddons/AMS');
    }

    protected function getViewMetricContainerDetails(AbstractAdapter $db, array $ids): array
    {
        return $db->fetchAllNum('
            SELECT article_id, publish_date, category_id
            FROM xf_xa_ams_article
            WHERE article_id in (' . $db->quote($ids) . ')
        ');
    }

    protected function updateViewTotals(array $viewMetrics): void
    {
        $db = \XF::db();
        foreach ($viewMetrics as $id => $viewCount)
        {
            /** @noinspection SqlResolve */
            $db->query('UPDATE xf_xa_ams_article SET view_count = view_count + ? WHERE article_id = ?', [$viewCount, $id]);
        }
    }
}