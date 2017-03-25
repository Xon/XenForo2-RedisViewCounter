<?php

namespace SV\RedisViewCounter\Repository;

use XF\Mvc\Entity\Repository;

class ContentView extends Repository
{
    public function logView($contentType, $contentId)
    {
        $app = \XF::app();
        $cache = $app->cache();
        if (!$cache || !method_exists($cache, 'getCredis') || !($credis = $cache->getCredis($cache)))
        {
            return false;
        }
        $useLua = method_exists($cache, 'useLua') && $cache->useLua();

        $key = $app->config['cache']['namespace'] . '[views]['.strval($contentType).']['.strval($contentId).']';

        $credis->incr($key);

        return true;
    }

    const LUA_GETDEL_SH1 = '6ba37a6998bb00d0b7f837a115df4b20388b71e0';
    const LUA_GETDEL_SCRIPT = "local oldVal = redis.call('GET', KEYS[1]) redis.call('DEL', KEYS[1]) return oldVal ";

    public function batchUpdateViews($contentType, $table, $contentIdCol, $viewsCol)
    {
        $app = \XF::app();
        $cache = $app->cache();
        if (!$cache || !method_exists($cache, 'getCredis') || !($credis = $cache->getCredis($cache)))
        {
            return false;
        }
        $useLua = method_exists($cache, 'useLua') && $cache->useLua();
        $pattern = $app->config['cache']['namespace'] . '[views]['.strval($contentType).'][';

        $sql = 'UPDATE {$table} SET {$viewsCol} = {$viewsCol} + ? where {$contentIdCol} = ?';

        // indicate to the redis instance would like to process X items at a time.
        $count = 100;
        // prevent looping forever
        $loopGuard = 10000;
        // find indexes matching the pattern
        $cursor = null;
        do
        {
            $keys = $credis->scan($cursor, $pattern ."*", $count);
            $loopGuard--;
            if ($keys === false)
            {
                break;
            }

            foreach($keys as $key)
            {
                $id = substr($key, strlen($pattern), -1);
                if (preg_match('/^[0-9]+$/', $id) != 1)
                {
                    continue;
                }
                // atomically get & delete the key
                if ($useLua)
                {
                    $view_count = $credis->evalSha(self::LUA_GETDEL_SH1, array($key), 1);
                    if (is_null($view_count))
                    {
                        $view_count = $credis->eval(self::LUA_GETDEL_SCRIPT, array($key), 1);
                    }
                }
                else
                {
                    $credis->pipeline()->multi();
                    $credis->get($key);
                    $credis->del($key);
                    $arrData = $credis->exec();
                    $view_count = $arrData[0];
                }
                $view_count = intval($view_count);
                // only update the database if a thread view happened
                if (!empty($view_count))
                {
                    $db->query($sql, array($view_count, $id));
                }
            }
        }
        while($loopGuard > 0 && !empty($cursor));

        return true;
    }
}