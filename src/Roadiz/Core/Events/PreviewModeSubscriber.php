<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use Pimple\Container;
use RZ\Roadiz\Core\Exceptions\PreviewNotAllowedException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class PreviewModeSubscriber
 * @package RZ\Roadiz\Core\Events
 */
class PreviewModeSubscriber implements EventSubscriberInterface
{
    protected $container;

    /**
     * PreviewModeSubscriber constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => ['onControllerMatched', 10],
            KernelEvents::RESPONSE => 'onResponse',
        ];
    }

    /**
     * @param ControllerEvent $event
     * @throws PreviewNotAllowedException
     */
    public function onControllerMatched(ControllerEvent $event)
    {
        if ($event->isMasterRequest()) {
            if (null === $this->container['securityTokenStorage']->getToken() ||
                !is_object($this->container['securityTokenStorage']->getToken()->getUser())) {
                throw new PreviewNotAllowedException();
            } elseif (!$this->container['securityAuthorizationChecker']->isGranted('ROLE_BACKEND_USER')) {
                throw new PreviewNotAllowedException();
            }
        }
    }

    /**
     * Enforce cache disabling.
     *
     * @param  ResponseEvent $event
     */
    public function onResponse(ResponseEvent $event)
    {
        $response = $event->getResponse();
        $response->expire();
        $response->headers->add(['X-Roadiz-Preview' => true]);
        $event->setResponse($response);
    }
}
