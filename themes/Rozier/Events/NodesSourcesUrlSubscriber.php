<?php
declare(strict_types=1);

namespace Themes\Rozier\Events;

use Doctrine\Common\Cache\CacheProvider;
use RZ\Roadiz\Core\Events\Node\NodeDeletedEvent;
use RZ\Roadiz\Core\Events\Node\NodeUndeletedEvent;
use RZ\Roadiz\Core\Events\Node\NodeUpdatedEvent;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesCreatedEvent;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesDeletedEvent;
use RZ\Roadiz\Core\Events\Translation\TranslationDeletedEvent;
use RZ\Roadiz\Core\Events\Translation\TranslationUpdatedEvent;
use RZ\Roadiz\Core\Events\UrlAlias\UrlAliasCreatedEvent;
use RZ\Roadiz\Core\Events\UrlAlias\UrlAliasDeletedEvent;
use RZ\Roadiz\Core\Events\UrlAlias\UrlAliasUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribe to Node, NodesSources and UrlAlias event to clear ns url cache.
 */
class NodesSourcesUrlSubscriber implements EventSubscriberInterface
{
    protected CacheProvider $cacheProvider;

    public function __construct(CacheProvider $cacheProvider)
    {
        $this->cacheProvider = $cacheProvider;
    }

    public static function getSubscribedEvents()
    {
        return [
            NodesSourcesCreatedEvent::class => 'purgeNSUrlCache',
            NodesSourcesDeletedEvent::class => 'purgeNSUrlCache',
            TranslationUpdatedEvent::class => 'purgeNSUrlCache',
            TranslationDeletedEvent::class => 'purgeNSUrlCache',
            NodeDeletedEvent::class => 'purgeNSUrlCache',
            NodeUndeletedEvent::class => 'purgeNSUrlCache',
            NodeUpdatedEvent::class => 'purgeNSUrlCache',
            UrlAliasCreatedEvent::class => 'purgeNSUrlCache',
            UrlAliasUpdatedEvent::class => 'purgeNSUrlCache',
            UrlAliasDeletedEvent::class => 'purgeNSUrlCache',
        ];
    }

    /**
     * Empty nodeSources Url cache
     */
    public function purgeNSUrlCache()
    {
        $this->cacheProvider->deleteAll();
    }
}
