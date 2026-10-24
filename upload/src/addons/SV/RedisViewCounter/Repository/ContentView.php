<?php

declare(strict_types=1);

namespace SV\RedisViewCounter\Repository;

use Credis_Client;
use SV\RedisCache\Repository\Redis as RedisRepo;
use SV\RedisViewCounter\Job\PushContentViewsToDatabaseJob;
use SV\RedisViewCounter\PushCountsToDatabase\AbstractHandler;
use SV\RedisViewCounter\PushCountsToDatabase\AMSArticleHandler;
use SV\RedisViewCounter\PushCountsToDatabase\AttachmentHandler;
use SV\RedisViewCounter\PushCountsToDatabase\PageHandler;
use SV\RedisViewCounter\PushCountsToDatabase\ResourceHandler;
use SV\RedisViewCounter\PushCountsToDatabase\ThreadHandler;
use SV\StandardLib\Helper;
use XF\Mvc\Entity\Repository;
use function count;
use function preg_match;

class ContentView extends Repository
{
    public const LUA_GET_DEL_SH1    = '6ba37a6998bb00d0b7f837a115df4b20388b71e0';
    public const LUA_GET_DEL_SCRIPT = "local oldVal = redis.call('GET', KEYS[1]) redis.call('DEL', KEYS[1]) return oldVal ";
    protected $initialBatchSize = 2000;
    protected $maxBatchSize = 10000;

    public static function get(): self
    {
        return Helper::repository(self::class);
    }

    public function logView(string $contentType, int $contentId): bool
    {
        $cache = RedisRepo::get()->getRedisConnector();
        if ($cache === null || !($credis = $cache->getCredis(false)))
        {
            return false;
        }

        $key = $cache->getNamespacedId('views_' . $contentType . '_' . $contentId);

        // this sets without an TTL or expiry date, and requires the batchUpdateViews to run to purge these entries!
        $credis->incr($key);

        return true;
    }

    public function batchUpdateViews(string $contentType): bool
    {
        $cache = RedisRepo::get()->getRedisConnector();
        if ($cache === null || !$cache->getCredis(false))
        {
            return false;
        }

        PushContentViewsToDatabaseJob::enqueue($contentType, $this->initialBatchSize);

        return true;
    }

    public function getMaxBatchSize(): int
    {
        return $this->maxBatchSize;
    }

    public function getHandler(string $contentType): ?AbstractHandler
    {
        switch ($contentType)
        {
            case ThreadHandler::CACHE_KEY: $class = ThreadHandler::class; break;
            case AttachmentHandler::CACHE_KEY: $class = AttachmentHandler::class; break;
            case PageHandler::CACHE_KEY: $class = PageHandler::class; break;
            case AMSArticleHandler::CACHE_KEY: $class = AMSArticleHandler::class; break;
            case ResourceHandler::CACHE_KEY: $class = ResourceHandler::class; break;
            //case 'thread':
            default: return null;
        }

        /** @var AbstractHandler $obj */
        $obj = Helper::newExtendedClass($class);
        if (!$obj->isActive())
        {
            return null;
        }

        return $obj;
    }

    /**
     * @param string|int|null $cursor
     * @param string          $contentType
     * @param int             $batch
     * @param float|int       $maxRunTime
     * @return int
     */
    public function incrementalBatchUpdateViews(&$cursor, string $contentType, int $batch, $maxRunTime): int
    {
        $handler = $this->getHandler($contentType);
        $pattern = 'views_' . $contentType . '_';
        $steps = 0;
        RedisRepo::get()->visitCacheByPattern($pattern, $cursor, $maxRunTime, function (Credis_Client $credis, array $keys) use ($handler, &$steps) {
            $scriptSh1 = static::LUA_GET_DEL_SH1;
            $viewMetrics = [];
            foreach ($keys as $key)
            {
                if (preg_match('/_([0-9]+)$/', $key, $match) !== 1)
                {
                    continue;
                }
                $steps++;
                $id = (int)$match[1];
                // atomically get & delete the key
                $viewCount = $credis->evalSha($scriptSh1, [$key], [1]);
                if ($viewCount === null)
                {
                    $viewCount = $credis->eval(static::LUA_GET_DEL_SCRIPT, [$key], [1]);
                }
                // $viewCount can be false if the key doesn't exist
                $viewCount = (int)$viewCount;
                // only update the database if a thread view happened
                if ($viewCount > 0)
                {
                    $viewMetrics[$id] += ($viewMetrics[$id] ?? 0) + $viewCount;
                }
            }

            if (count($viewMetrics) === 0)
            {
                return;
            }
            $handler->batchDatabaseUpdate($viewMetrics);
        }, $batch);

        return $steps;
    }

    /**
     * @deprecated
     */
    protected function logDatabaseUpdate(string $sql, string $contentType, int $id, int $viewCount)
    {
    }
}