<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use Pimple\Container;
use RZ\Roadiz\Core\Exceptions\PreviewNotAllowedException;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\KernelInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @package RZ\Roadiz\Core\Events
 */
class PreviewModeSubscriber implements EventSubscriberInterface
{
    const QUERY_PARAM_NAME = '_preview';
    const PREVIEW_ROLE = 'ROLE_BACKEND_USER';

    /**
     * @var Container
     */
    protected $container;
    /**
     * @var KernelInterface
     */
    private KernelInterface $kernel;

    /**
     * @param Container $container
     */
    public function __construct(KernelInterface $kernel, Container $container)
    {
        $this->container = $container;
        $this->kernel = $kernel;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 9999],
            KernelEvents::CONTROLLER => ['onControllerMatched', 10],
            KernelEvents::RESPONSE => 'onResponse',
        ];
    }

    /**
     * @return bool
     */
    protected function supports()
    {
        return $this->kernel->isPreview();
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if ($event->isMasterRequest() &&
            $this->kernel instanceof Kernel &&
            $event->getRequest()->query->has(static::QUERY_PARAM_NAME) &&
            (bool) ($event->getRequest()->query->get(static::QUERY_PARAM_NAME, 0)) === true) {
            $this->kernel->setPreview(true);
        }
    }

    /**
     * @param ControllerEvent $event
     * @throws PreviewNotAllowedException
     */
    public function onControllerMatched(ControllerEvent $event)
    {
        if ($this->supports() && $event->isMasterRequest()) {
            if (null === $this->container['securityTokenStorage']->getToken() ||
                !is_object($this->container['securityTokenStorage']->getToken()->getUser())) {
                throw new PreviewNotAllowedException();
            } elseif (!$this->container['securityAuthorizationChecker']->isGranted(static::PREVIEW_ROLE)) {
                throw new PreviewNotAllowedException();
            }
        }
    }

    /**
     * Enforce cache disabling.
     *
     * @param ResponseEvent $event
     */
    public function onResponse(ResponseEvent $event)
    {
        if ($this->supports()) {
            $response = $event->getResponse();
            $response->expire();
            $response->headers->add(['X-Roadiz-Preview' => true]);
            $event->setResponse($response);
        }
    }
}
