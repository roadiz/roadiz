<?php
/**
 * Copyright (c) 2018. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file UserLocaleSubscriber.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Core\Events;

use Pimple\Container;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
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
     * UserLocaleSubscriber constructor.
     *
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
            UserEvents::USER_UPDATED => [['onUserUpdated']],
        ];
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if (!$request->hasPreviousSession()) {
            return;
        }

        // try to see if the locale has been set as a _locale routing parameter
        if ($request->getSession()->has('_locale')) {
            // if no explicit locale has been set on this request, use one from the session
            $request->setLocale($request->getSession()->get('_locale'));
            \Locale::setDefault($request->getSession()->get('_locale'));
        }
    }

    /**
     * @param InteractiveLoginEvent $event
     */
    public function onInteractiveLogin(InteractiveLoginEvent $event)
    {
        $user = $event->getAuthenticationToken()->getUser();
        $session = $event->getRequest()->getSession();

        if (null !== $session &&
            $user instanceof User &&
            null !== $user->getLocale()) {
            $session->set('_locale', $user->getLocale());
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
