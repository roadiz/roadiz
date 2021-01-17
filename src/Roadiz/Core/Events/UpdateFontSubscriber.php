<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use Pimple\Container;
use RZ\Roadiz\Core\Events\Font\PreUpdatedFontEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Calls font life cycle methods when no data changed according to Doctrine.
 *
 * @package RZ\Roadiz\Core\Events
 */
class UpdateFontSubscriber implements EventSubscriberInterface
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            PreUpdatedFontEvent::class => 'onPreUpdatedFont'
        ];
    }

    public function onPreUpdatedFont(PreUpdatedFontEvent $event)
    {
        $font = $event->getFont();
        if (null !== $font) {
            /*
             * Force updating files if uploaded
             * as doctrine wont see any changes.
             */
            $fontSubscriber = new FontLifeCycleSubscriber($this->container);
            $fontSubscriber->setFontFilesNames($font);
            $fontSubscriber->upload($font);
        }
    }
}
