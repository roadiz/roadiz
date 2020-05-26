<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use RZ\Roadiz\Core\Events\Cache\CachePurgeAssetsRequestEvent;
use RZ\Roadiz\Core\Events\Cache\CachePurgeRequestEvent;

final class CacheEvents
{
    /**
     * @deprecated
     */
    const PURGE_REQUEST = CachePurgeRequestEvent::class;
    /**
     * @deprecated
     */
    const PURGE_ASSETS_REQUEST = CachePurgeAssetsRequestEvent::class;
}
