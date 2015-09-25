<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
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
 * @file ResponseHeaderSubscriber.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Events;

use RZ\Roadiz\Core\Entities\Role;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

/**
 * Add RZ-AuthentifiedUser and RZ-BackendUser variable to headers to enable cache varying.
 */
class ResponseHeaderSubscriber implements EventSubscriberInterface
{
    protected $authorizationChecker;
    protected $tokenStorage;

    public function __construct(AuthorizationChecker $authorizationChecker = null, TokenStorage $tokenStorage = null)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
    }
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => 'onResponse',
        ];
    }

    public function onResponse(FilterResponseEvent $event)
    {
        $response = $event->getResponse();

        if (null !== $this->tokenStorage &&
            null !== $this->tokenStorage->getToken() &&
            is_object($this->tokenStorage->getToken()->getUser())) {
            $response->headers->add([
                'RZ-Authentified' => true,
            ]);


            if (null !== $this->authorizationChecker &&
                $this->authorizationChecker->isGranted(Role::ROLE_BACKEND_USER)) {
                $response->headers->add([
                    'RZ-Backend' => true,
                ]);
            } else {
                $response->headers->add([
                    'RZ-Backend' => false,
                ]);
            }
        }

        $response->setVary(['RZ-Authentified', 'RZ-Backend'], false);

        $event->setResponse($response);
    }
}
