<?php

declare(strict_types=1);

namespace SV\RedisViewCounter\XFRM\Repository;

use SV\RedisViewCounter\PushCountsToDatabase\ResourceHandler;
use SV\RedisViewCounter\Repository\ContentView;

/**
 * @extends \XFRM\Repository\ResourceItem
 */
class ResourceItem extends XFCP_ResourceItem
{
    public function logResourceView(\XFRM\Entity\ResourceItem $resource)
    {
        if (ContentView::get()->logView(ResourceHandler::CACHE_KEY, $resource->resource_id))
        {
            return;
        }
        parent::logResourceView($resource);
    }

    public function batchUpdateResourceViews()
    {
        if (ContentView::get()->batchUpdateViews(ResourceHandler::CACHE_KEY))
        {
            return;
        }
        parent::batchUpdateResourceViews();
    }
}