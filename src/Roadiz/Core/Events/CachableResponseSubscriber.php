<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CachableResponseSubscriber implements EventSubscriberInterface
{
    /**
     * @var bool
     */
    private $cachable = false;
    /**
     * @var int
     */
    private $minutes = 0;
    /**
     * @var bool
     */
    private bool $allowClientCache;

    /**
     * @param int $minutes
     * @param bool $cachable
     * @param bool $allowClientCache
     */
    public function __construct(int $minutes, bool $cachable = true, bool $allowClientCache = false)
    {
        $this->minutes = $minutes;
        $this->cachable = $cachable;
        $this->allowClientCache = $allowClientCache;
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
        $response->setSharedMaxAge(60 * $this->minutes);
        $response->headers->addCacheControlDirective('must-revalidate', true);

        if ($this->allowClientCache) {
            $response->setMaxAge(60 * $this->minutes);
        } else {
            $response->headers->addCacheControlDirective('no-store', true);
        }

        $response->setVary('Accept-Encoding, X-Partial, x-requested-with');

        if ($event->getRequest()->isXmlHttpRequest()) {
            $response->headers->add([
                'X-Partial' => true
            ]);
        }
    }
}
