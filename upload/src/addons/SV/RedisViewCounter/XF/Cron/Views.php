<?php

namespace SV\RedisViewCounter\XF\Cron;

use SV\RedisViewCounter\XF\Repository\Page as PageRepo;

/**
 * Extends \XF\Cron\Views
 */
class Views extends XFCP_Views
{
    public static function runViewUpdate()
    {
        parent::runViewUpdate();

        /** @var PageRepo $attachmentRepo */
        $attachmentRepo = \XF::app()->repository('XF:Page');
        $attachmentRepo->batchUpdateViews();
    }
}