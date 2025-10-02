<?php

declare(strict_types=1);

namespace SV\RedisViewCounter\Repository;

use SV\RedisCache\Repository\Redis as RedisRepo;
use SV\StandardLib\Helper;
use XF\Mvc\Entity\Repository;
use function intval;
use function preg_match;
use function str_replace;
use function strlen;
use function substr;

class ContentView extends Repository
{
    public const LUA_GET_DEL_SH1    = '6ba37a6998bb00d0b7f837a115df4b20388b71e0';
    public const LUA_GET_DEL_SCRIPT = "local oldVal = redis.call('GET', KEYS[1]) redis.call('DEL', KEYS[1]) return oldVal ";

    public static function get(): self
    {
        return Helper::repository(self::class);
    }

    public function logView(string $contentType, int $contentId): bool
    {
        $cache = RedisRepo::get()->getRedisConnector();
        if ($cache === null || !($credis = $cache->getCredis()))
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
        if ($cache === null || !($credis = $cache->getCredis(false)))
        {
            return false;
        }
        $escaped = $pattern = $cache->getNamespacedId('views_' . $contentType . '_');
        $escaped = str_replace('[', '\[', $escaped);
        $escaped = str_replace(']', '\]', $escaped);
        $escaped .= '*';

        $sql = "UPDATE {$table} SET {$viewsCol} = {$viewsCol} + ? where {$contentIdCol} = ?";

        $dbSize = $credis->dbsize() ?: 100000;
        // indicate to the redis instance would like to process X items at a time. This is before the pattern match is applied!
        $count = 1000;
        $loopGuard = ($dbSize / $count) + 10;
        // only valid values for cursor are null (the stack turns it into a 0) or whatever scan return
        $cursor = null;
        do
        {
            $keys = $credis->scan($cursor, $escaped, $count);
            $loopGuard--;
            if ($keys === false)
            {
                break;
            }

            foreach ($keys as $key)
            {
                $id = substr($key, strlen($pattern), strlen($key) - strlen($pattern));
                if (preg_match('/^[0-9]+$/', $id) != 1)
                {
                    continue;
                }
                // atomically get & delete the key
                /** @var int|null $viewCount */
                $viewCount = $credis->evalSha(self::LUA_GET_DEL_SH1, [$key], [1]);
                if ($viewCount === null)
                {
                    /** @var int $viewCount */
                    $viewCount = $credis->eval(self::LUA_GET_DEL_SCRIPT, [$key], [1]);
                }
                $viewCount = intval($viewCount);
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
     * @return void
     * @noinspection PhpUnusedParameterInspection
     * @noinspection PhpDocMissingThrowsInspection
     */
    protected function logDatabaseUpdate(string $sql, string $contentType, int $id, int $viewCount)
    {
        $this->db()->query($sql, [$viewCount, $id]);
    }
}