<?php

namespace SV\RedisViewCounter\XF\Cron;



/**
 * Extends \XF\Cron\Views
 */
class Views extends XFCP_Views
{
    public static function runViewUpdate()
    {
        parent::runViewUpdate();

        /** @var \SV\RedisViewCounter\XF\Repository\Page $attachmentRepo */
        $attachmentRepo = \XF::app()->repository('XF:Page');
        $attachmentRepo->batchUpdateViews();
    }
}