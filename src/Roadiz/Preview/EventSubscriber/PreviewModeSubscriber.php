<?php
declare(strict_types=1);

namespace RZ\Roadiz\Preview\EventSubscriber;

use RZ\Roadiz\Preview\Exception\PreviewNotAllowedException;
use RZ\Roadiz\Preview\PreviewAwareInterface;
use RZ\Roadiz\Preview\PreviewResolverInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class PreviewModeSubscriber implements EventSubscriberInterface
{
    const QUERY_PARAM_NAME = '_preview';

    protected PreviewResolverInterface $previewResolver;
    protected TokenStorageInterface $tokenStorage;
    protected AuthorizationCheckerInterface $authorizationChecker;

    /**
     * @param PreviewResolverInterface $previewResolver
     * @param TokenStorageInterface $tokenStorage
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(
        PreviewResolverInterface $previewResolver,
        TokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->previewResolver = $previewResolver;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
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
            if ($request instanceof PreviewAwareInterface) {
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
            $token = $this->tokenStorage->getToken();
            if (null === $token || !$token->isAuthenticated()) {
                throw new PreviewNotAllowedException('You are not authenticated to use preview mode.');
            }
            if (!$this->authorizationChecker->isGranted($this->previewResolver->getRequiredRole())) {
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
