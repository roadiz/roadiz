<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use RZ\Roadiz\Core\Events\UrlAlias\UrlAliasCreatedEvent;
use RZ\Roadiz\Core\Events\UrlAlias\UrlAliasDeletedEvent;
use RZ\Roadiz\Core\Events\UrlAlias\UrlAliasUpdatedEvent;

/**
 * @deprecated
 */
final class UrlAliasEvents
{
    /**
     * Event urlAlias.created is triggered each time an url-alias
     * is created.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterUrlAliasEvent instance
     *
     * @var string
     * @deprecated
     */
    const URL_ALIAS_CREATED = UrlAliasCreatedEvent::class;

    /**
     * Event urlAlias.updated is triggered each time an url-alias
     * is updated.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterUrlAliasEvent instance
     *
     * @var string
     * @deprecated
     */
    const URL_ALIAS_UPDATED = UrlAliasUpdatedEvent::class;

    /**
     * Event urlAlias.deleted is triggered each time an url-alias
     * is deleted.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterUrlAliasEvent instance
     *
     * @var string
     * @deprecated
     */
    const URL_ALIAS_DELETED = UrlAliasDeletedEvent::class;
}
