<?php

declare(strict_types=1);

namespace SV\RedisViewCounter\Repository;

use Credis_Client;
use SV\RedisCache\Repository\Redis as RedisRepo;
use SV\StandardLib\Helper;
use XF\Mvc\Entity\Repository;
use function preg_match;

class ContentView extends Repository
{
    public const LUA_GET_DEL_SH1    = '6ba37a6998bb00d0b7f837a115df4b20388b71e0';
    public const LUA_GET_DEL_SCRIPT = "local oldVal = redis.call('GET', KEYS[1]) redis.call('DEL', KEYS[1]) return oldVal ";
    protected $batchSize = 1000;

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

    public function batchUpdateViews(string $contentType, string $table, string $contentIdCol, string $viewsCol): bool
    {
        $cache = RedisRepo::get()->getRedisConnector();
        if ($cache === null || !$cache->getCredis(false))
        {
            return false;
        }

        $cursor = null;
        $sql = "UPDATE `{$table}` SET `{$viewsCol}` = `{$viewsCol}` + ? WHERE `{$contentIdCol}` = ?";

        $pattern = 'views_' . $contentType . '_';
        RedisRepo::get()->visitCacheByPattern($pattern, $cursor, 0, function (Credis_Client $credis, array $keys) use ($contentType, $sql) {
            foreach ($keys as $key)
            {
                if (preg_match('/_([0-9]+)$/', $key, $match) !== 1)
                {
                    continue;
                }
                $id = (int)$match[1];
                // atomically get & delete the key
                $viewCount = $credis->evalSha(self::LUA_GET_DEL_SH1, [$key], [1]);
                if ($viewCount === null)
                {
                    $viewCount = $credis->eval(self::LUA_GET_DEL_SCRIPT, [$key], [1]);
                }
                $viewCount = (int)$viewCount;
                // only update the database if a thread view happened
                if ($viewCount > 0)
                {
                    $this->logDatabaseUpdate($sql, $contentType, $id, $viewCount);
                }
            }
        }, $this->batchSize, $cache);

        return true;
    }

    /**
     * @param string $sql
     * @param string $contentType
     * @param int    $id
     * @param int    $viewCount
     * @return void
     * @noinspection PhpUnusedParameterInspection
     * @noinspection PhpDocMissingThrowsInspection
     */
    protected function logDatabaseUpdate(string $sql, string $contentType, int $id, int $viewCount)
    {
        \XF::db()->query($sql, [$viewCount, $id]);
    }
}