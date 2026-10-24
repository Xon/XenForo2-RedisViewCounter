<?php

namespace SV\RedisViewCounter\Job;

use SV\RedisViewCounter\Repository\ContentView;
use XF\Job\AbstractJob;
use XF\Job\JobResult;
use function microtime;

class PushContentViewsToDatabaseJob extends AbstractJob
{
    public static function enqueue(string $contentType, int $batch): ?int
    {
        return \XF::app()->jobManager()->enqueue(
            PushContentViewsToDatabaseJob::class, [
            'contentType'  => $contentType,
            'batch'        => $batch,
        ], false);
    }

    protected $defaultData = [
        'contentType'  => null,
        'steps'        => 0,
        'cursor'       => null, // null - start new, 0 - stop, otherwise it is a blob returned from redis
        'batch'        => 1000,
    ];

    /**
     * @param float|int $maxRunTime
     * @return JobResult
     */
    public function run($maxRunTime): JobResult
    {
        $contentType = $this->data['contentType'];
        $batch = $this->data['batch'];

        if ($contentType === null)
        {
            return $this->complete();
        }

        /** @var string|int|null $cursor */
        $cursor = $this->data['cursor'];

        $startTime = microtime(true);

        $repo = ContentView::get();
        $steps = $repo->incrementalBatchUpdateViews($cursor, $contentType, $batch, $maxRunTime);
        if (!$cursor)
        {
            return $this->complete();
        }

        $this->data['steps'] += $steps;
        $this->data['cursor'] = $cursor;
        $this->data['batch'] = $this->calculateOptimalBatch($this->data['batch'], $steps, $startTime, $maxRunTime, $repo->getMaxBatchSize());

        return $this->resume();
    }

    public function getStatusMessage(): string
    {
        return '';
    }

    public function canCancel(): bool
    {
        return false;
    }

    public function canTriggerByChoice(): bool
    {
        return false;
    }
}