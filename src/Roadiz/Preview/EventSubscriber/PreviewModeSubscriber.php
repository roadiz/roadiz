<?php
declare(strict_types=1);

namespace RZ\Roadiz\Preview\EventSubscriber;

use Pimple\Container;
use RZ\Roadiz\Core\HttpFoundation\Request as RoadizRequest;
use RZ\Roadiz\Preview\Exception\PreviewNotAllowedException;
use RZ\Roadiz\Preview\PreviewResolverInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @package RZ\Roadiz\Core\Events
 */
class PreviewModeSubscriber implements EventSubscriberInterface
{
    const QUERY_PARAM_NAME = '_preview';
    const PREVIEW_ROLE = 'ROLE_BACKEND_USER';
    /**
     * @var PreviewResolverInterface
     */
    protected $previewResolver;
    /**
     * @var Container
     */
    protected $container;

    /**
     * @param Container $container
     */
    public function __construct(PreviewResolverInterface $previewResolver, Container $container)
    {
        $this->container = $container;
        $this->previewResolver = $previewResolver;
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
        return $this->previewResolver->isPreview();
    }

    /**
     * @param RequestEvent $event
     */
    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        if ($event->isMasterRequest() &&
            $request->query->has(static::QUERY_PARAM_NAME) &&
            (bool) ($request->query->get(static::QUERY_PARAM_NAME, 0)) === true) {
            if ($request instanceof RoadizRequest) {
                $request->setPreview(true);
            }
        }
    }

    /**
     * Preview mode security enforcement.
     * You MUST check here is user can use preview mode BEFORE going
     * any further into your app logic.
     *
     * @param ControllerEvent $event
     * @throws PreviewNotAllowedException
     */
    public function onControllerMatched(ControllerEvent $event)
    {
        if ($this->supports() && $event->isMasterRequest()) {
            /** @var TokenInterface|null $token */
            $token = $this->container['securityTokenStorage']->getToken();
            if (null === $token || !$token->isAuthenticated()) {
                throw new PreviewNotAllowedException('You are not authenticated to use preview mode.');
            }
            if (!$this->container['securityAuthorizationChecker']->isGranted(static::PREVIEW_ROLE)) {
                throw new PreviewNotAllowedException('You are not granted to use preview mode.');
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
