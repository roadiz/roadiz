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
 * @file PreviewModeSubscriber.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Events;

use Pimple\Container;
use RZ\Roadiz\Core\Exceptions\PreviewNotAllowedException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
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
     * @param FilterControllerEvent $event
     * @throws PreviewNotAllowedException
     */
    public function onControllerMatched(FilterControllerEvent $event)
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
     * @param  FilterResponseEvent $event
     */
    public function onResponse(FilterResponseEvent $event)
    {
        $response = $event->getResponse();
        $response->expire();
        $response->headers->add(['X-Roadiz-Preview' => true]);
        $event->setResponse($response);
    }
}
