<?php

namespace SV\RedisViewCounter\Repository;

use SV\RedisCache\Redis;
use XF\Mvc\Entity\Repository;

class ContentView extends Repository
{
    public const LUA_GET_DEL_SH1    = '6ba37a6998bb00d0b7f837a115df4b20388b71e0';
    public const LUA_GET_DEL_SCRIPT = "local oldVal = redis.call('GET', KEYS[1]) redis.call('DEL', KEYS[1]) return oldVal ";

    public function logView(string $contentType, int $contentId): bool
    {
        $app = $this->app();
        /** @var Redis $cache */
        $cache = $app->cache();
        if (!($cache instanceof Redis) || !($credis = $cache->getCredis()))
        {
            return false;
        }

        $key = $cache->getNamespacedId('views_' . $contentType . '_' . $contentId);

        $credis->incr($key);

        return true;
    }

    public function batchUpdateViews(string $contentType, string $table, string $contentIdCol, string $viewsCol): bool
    {
        $app = $this->app();
        /** @var Redis $cache */
        $cache = $app->cache();
        if (!($cache instanceof Redis) || !($credis = $cache->getCredis(false)))
        {
            return false;
        }
        $escaped = $pattern = $cache->getNamespacedId('views_' . $contentType . '_');
        $escaped = str_replace('[', '\[', $escaped);
        $escaped = str_replace(']', '\]', $escaped);

        $sql = "UPDATE {$table} SET {$viewsCol} = {$viewsCol} + ? where {$contentIdCol} = ?";

        $dbSize = $credis->dbsize() ?: 100000;
        // indicate to the redis instance would like to process X items at a time. This is before the pattern match is applied!
        $count = 1000;
        $loopGuard = ($dbSize / $count) + 10;
        // only valid values for cursor are null (the stack turns it into a 0) or whatever scan return
        $cursor = null;
        do
        {
            $keys = $credis->scan($cursor, $escaped . '*', $count);
            $loopGuard--;
            if ($keys === false)
            {
                break;
            }

            foreach ($keys as $key)
            {
                $id = substr($key, \strlen($pattern), \strlen($key) - \strlen($pattern));
                if (preg_match('/^[0-9]+$/', $id) != 1)
                {
                    continue;
                }
                // atomically get & delete the key
                $viewCount = $credis->evalSha(self::LUA_GET_DEL_SH1, [$key], [1]);
                if ($viewCount === null)
                {
                    $viewCount = $credis->eval(self::LUA_GET_DEL_SCRIPT, [$key], [1]);
                }
                $viewCount = \intval($viewCount);
                // only update the database if a thread view happened
                if ($viewCount > 0)
                {
                    $this->logDatabaseUpdate($sql, $contentType, $id, $viewCount);
                }
            }
        }
        while ($loopGuard > 0 && !empty($cursor));

        return true;
    }

    /**
     * @param string $sql
     * @param string $contentType
     * @param int    $id
     * @param int    $viewCount
     * @noinspection PhpUnusedParameterInspection
     * @noinspection PhpDocMissingThrowsInspection
     */
    protected function logDatabaseUpdate(string $sql, string $contentType, int $id, int $viewCount)
    {
        $this->db()->query($sql, [$viewCount, $id]);
    }
}