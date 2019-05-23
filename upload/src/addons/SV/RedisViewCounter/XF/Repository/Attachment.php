<?php

namespace SV\RedisViewCounter\XF\Repository;

use SV\RedisViewCounter\Repository\ContentView;

class Attachment extends XFCP_Attachment
{
    public function logAttachmentView(\XF\Entity\Attachment $attachment)
    {
        /** @var ContentView $contentView */
        $contentView = $this->repository('SV\RedisViewCounter:ContentView');
        if ($contentView->logView('attachment', $attachment->attachment_id))
        {
            return;
        }
        parent::logAttachmentView($attachment);
    }

    public function batchUpdateAttachmentViews()
    {
        /** @var ContentView $contentView */
        $contentView = $this->repository('SV\RedisViewCounter:ContentView');
        if ($contentView->batchUpdateViews('attachment', 'xf_attachment', 'attachment_id', 'view_count'))
        {
            return;
        }
        parent::batchUpdateAttachmentViews();
    }
}