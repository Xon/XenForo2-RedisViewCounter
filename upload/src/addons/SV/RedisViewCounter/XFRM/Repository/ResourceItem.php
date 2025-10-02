<?php

declare(strict_types=1);

namespace SV\RedisViewCounter\XFRM\Repository;

use SV\RedisViewCounter\Repository\ContentView;

/**
 * @extends \XFRM\Repository\ResourceItem
 */
class ResourceItem extends XFCP_ResourceItem
{
    public function logResourceView(\XFRM\Entity\ResourceItem $resource)
    {
        if (ContentView::get()->logView('xfrm_resource', $resource->resource_id))
        {
            return;
        }
        parent::logResourceView($resource);
    }

    public function batchUpdateResourceViews()
    {
        if (ContentView::get()->batchUpdateViews('xfrm_resource', 'xf_rm_resource', 'resource_id', 'view_count'))
        {
            return;
        }
        parent::batchUpdateResourceViews();
    }
}