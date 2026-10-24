<?php

declare(strict_types=1);

namespace SV\RedisViewCounter\PushCountsToDatabase;

use SV\RedisViewCounter\Repository\ContentView;
use SV\StandardLib\Helper;
use XF\Db\AbstractAdapter;
use XF\Repository\ActivityLogRepository;
use function array_keys;
use function count;

/**
 * Implementations must be registered manually in @see ContentView::getHandler
 */
abstract class AbstractHandler
{
    public function isActive(): bool
    {
        return true;
    }

    public function batchDatabaseUpdate(array $viewMetrics): void
    {
        $this->updateViewTotals($viewMetrics);
        if (\XF::$versionId < 2030000)
        {
            return;
        }
        $this->logActivity($viewMetrics);
    }

    protected function logActivity(array $viewMetrics): void
    {
        $entityContentType = $this->getEntityContentType();
        if ($entityContentType === null)
        {
            return;
        }
        $viewMetrics = $this->createViewMetricsForActivityLog($viewMetrics);
        if (count($viewMetrics) === 0)
        {
            return;
        }
        $activityLogRepo = Helper::repository(ActivityLogRepository::class);
        $activityLogRepo->bulkLog($entityContentType, $viewMetrics);
    }

    public function createViewMetricsForActivityLog(array $rawViewMetrics): array
    {
        $recs = $this->getViewMetricContainerDetails(\XF::db(), array_keys($rawViewMetrics));
        if (count($recs) === 0)
        {
            return [];
        }

        $viewMetrics = [];
        foreach ($recs as $rec)
        {
            [$id, $contentDate, $containerId] = $rec;
            $viewCount = (int)($rawViewMetrics[$id] ?? 0);
            if ($viewCount === 0)
            {
                continue;
            }

            $viewMetrics[] = [
                'content_id'           => $id,
                'content_date'         => $contentDate,
                'content_container_id' => $containerId,
                'view_count'           => $viewCount,
                'log_date'             => \XF::$time,
            ];
        }

        return $viewMetrics;
    }

    abstract protected function updateViewTotals(array $viewMetrics): void;
    abstract protected function getEntityContentType(): ?string;
    abstract protected function getViewMetricContainerDetails(AbstractAdapter $db, array $ids): array;
}