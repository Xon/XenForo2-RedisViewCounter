<?php

namespace SV\RedisViewCounter\XF\Repository;

class Attachment extends XFCP_Attachment
{
    public function logAttachmentView(\XF\Entity\Attachment $attachment)
    {
        /** @var \SV\RedisViewCounter\Repository\ContentView $contentView */
        $contentView = $this->repository('\SV\RedisViewCounter\Repository\ContentView');
        if ($contentView->logView('attachment', $attachment->attachment_id))
        {
            return;
        }
        parent::logAttachmentView($attachment);
    }

    public function batchUpdateAttachmentViews()
    {
        /** @var \SV\RedisViewCounter\Repository\ContentView $contentView */
        $contentView = $this->repository('\SV\RedisViewCounter\Repository\ContentView');
        if ($contentView->batchUpdateViews('attachment', 'xf_attachment', 'attachment_id', 'view_count'))
        {
            return;
        }
        parent::batchUpdateAttachmentViews();
    }
}