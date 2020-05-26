<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use RZ\Roadiz\Core\Kernel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event dispatched to setup theme configuration at kernel request.
 */
class LocaleSubscriber implements EventSubscriberInterface
{
    private $kernel;

    /**
     * @param Kernel $kernel
     */
    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        /*
         * Locale subscriber has HIGH priority over Firewall and Routing
         */
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 70],
        ];
    }

    /**
     * After a controller has been matched. We need to inject current
     * Kernel instance and main DI container.
     *
     * @param RequestEvent $event
     */
    public function onKernelRequest(RequestEvent $event)
    {
        if ($event->isMasterRequest()) {
            $request = $event->getRequest();
            /*
             * Set default locale
             */
            if ($request->attributes->has('_locale') &&
                $request->attributes->get('_locale') !== '') {
                $locale = $request->attributes->get('_locale');
                $event->getRequest()->setLocale($locale);
                \Locale::setDefault($locale);
            } elseif (null !== $translation = $this->kernel->get('defaultTranslation')) {
                $shortLocale = $translation->getLocale();
                $event->getRequest()->setLocale($shortLocale);
                \Locale::setDefault($shortLocale);
            }
        }
    }
}
