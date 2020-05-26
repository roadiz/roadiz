<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use RZ\Roadiz\Core\Events\Translation\TranslationCreatedEvent;
use RZ\Roadiz\Core\Events\Translation\TranslationDeletedEvent;
use RZ\Roadiz\Core\Events\Translation\TranslationUpdatedEvent;

/**
 * @deprecated
 */
final class TranslationEvents
{
    /**
     * Event translation.created is triggered each time a translation
     * is created.
     *
     * Evetn listener will be given a:
     * RZ\Roadiz\Core\Events\FilterTranslationEvent instance
     *
     * @var string
     * @deprecated
     */
    const TRANSLATION_CREATED = TranslationCreatedEvent::class;

    /**
     * Event translation.updated is triggered each time a translation
     * is updated.
     *
     * Evetn listener will be given a:
     * RZ\Roadiz\Core\Events\FilterTranslationEvent instance
     *
     * @var string
     * @deprecated
     */
    const TRANSLATION_UPDATED = TranslationUpdatedEvent::class;

    /**
     * Event translation.deleted is triggered each time a translation
     * is deleted.
     *
     * Evetn listener will be given a:
     * RZ\Roadiz\Core\Events\FilterTranslationEvent instance
     *
     * @var string
     * @deprecated
     */
    const TRANSLATION_DELETED = TranslationDeletedEvent::class;
}
