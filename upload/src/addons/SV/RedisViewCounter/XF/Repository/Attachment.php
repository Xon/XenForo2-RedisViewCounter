<?php

namespace SV\RedisViewCounter\XF\Repository;

use SV\RedisViewCounter\Repository\ContentView;

class Attachment extends XFCP_Attachment
{
    public function logAttachmentView(\XF\Entity\Attachment $attachment)
    {
        if (ContentView::get()->logView('attachment', $attachment->attachment_id))
        {
            return;
        }
        parent::logAttachmentView($attachment);
    }

    public function batchUpdateAttachmentViews()
    {
        if (ContentView::get()->batchUpdateViews('attachment', 'xf_attachment', 'attachment_id', 'view_count'))
        {
            return;
        }
        parent::batchUpdateAttachmentViews();
    }
}