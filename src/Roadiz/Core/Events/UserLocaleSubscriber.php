<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use Pimple\Container;
use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\Core\Events\User\UserUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class UserLocaleSubscriber implements EventSubscriberInterface
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
     * @return array
     */
    public static function getSubscribedEvents()
    {
        // must be registered after the default Locale listener
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 32]],
            SecurityEvents::INTERACTIVE_LOGIN => [['onInteractiveLogin', 15]],
            UserUpdatedEvent::class => [['onUserUpdated']],
        ];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        if (!$request->hasPreviousSession()) {
            return;
        }
        $session = $request->getSession();

        // try to see if the locale has been set as a _locale routing parameter
        if ($session->has('_locale')) {
            // if no explicit locale has been set on this request, use one from the session
            $request->setLocale($session->get('_locale'));
            \Locale::setDefault($session->get('_locale'));
        }
    }

    /**
     * @param InteractiveLoginEvent $event
     */
    public function onInteractiveLogin(InteractiveLoginEvent $event)
    {
        $user = $event->getAuthenticationToken()->getUser();

        if ($event->getRequest()->hasPreviousSession()) {
            $session = $event->getRequest()->getSession();
            if (null !== $session &&
                $user instanceof User &&
                null !== $user->getLocale()) {
                $session->set('_locale', $user->getLocale());
            }
        }
    }

    /**
     * @param FilterUserEvent $event
     */
    public function onUserUpdated(FilterUserEvent $event)
    {
        /** @var RequestStack $requestStack */
        $requestStack = $this->container['requestStack'];

        /** @var TokenStorage $tokenStorage */
        $tokenStorage = $this->container['securityTokenStorage'];

        $user = $event->getUser();
        $request = $requestStack->getMasterRequest();

        if (null !== $request &&
            $request->hasPreviousSession() &&
            null !== $request->getSession() &&
            null !== $tokenStorage->getToken() &&
            $tokenStorage->getToken()->getUser() instanceof User &&
            $tokenStorage->getToken()->getUsername() === $user->getUsername()
        ) {
            if (null === $user->getLocale()) {
                $request->getSession()->remove('_locale');
            } else {
                $request->getSession()->set('_locale', $user->getLocale());
            }
        }
    }
}
