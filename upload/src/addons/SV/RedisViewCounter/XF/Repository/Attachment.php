<?php

declare(strict_types=1);

namespace SV\RedisViewCounter\XF\Repository;

use SV\RedisViewCounter\PushCountsToDatabase\AttachmentHandler;
use SV\RedisViewCounter\Repository\ContentView;

class Attachment extends XFCP_Attachment
{
    public function logAttachmentView(\XF\Entity\Attachment $attachment)
    {
        if (ContentView::get()->logView(AttachmentHandler::CACHE_KEY, $attachment->attachment_id))
        {
            return;
        }
        parent::logAttachmentView($attachment);
    }

    public function batchUpdateAttachmentViews()
    {
        if (ContentView::get()->batchUpdateViews(AttachmentHandler::CACHE_KEY))
        {
            return;
        }
        parent::batchUpdateAttachmentViews();
    }
}