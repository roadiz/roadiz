<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CachableResponseSubscriber implements EventSubscriberInterface
{
    private $cachable = false;
    private $minutes = 0;

    /**
     * @param int  $minutes
     * @param bool $cachable
     */
    public function __construct(int $minutes, bool $cachable = true)
    {
        $this->minutes = $minutes;
        $this->cachable = $cachable;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -1001],
        ];
    }

    public function onKernelResponse(ResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }
        if ($this->cachable === false || $this->minutes <= 0) {
            return;
        }
        header_remove('Cache-Control');
        header_remove('Vary');
        $response = $event->getResponse();
        $response->headers->remove('cache-control');
        $response->headers->remove('vary');
        $response->setPublic();
        $response->setMaxAge(60 * $this->minutes);
        $response->setSharedMaxAge(60 * $this->minutes);
        $response->headers->addCacheControlDirective('must-revalidate', true);
        $response->setVary('Accept-Encoding, X-Partial, x-requested-with');

        if ($event->getRequest()->isXmlHttpRequest()) {
            $response->headers->add([
                'X-Partial' => true
            ]);
        }
    }
}
