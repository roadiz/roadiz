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
 * @file LocaleSubscriber.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Events;

use RZ\Roadiz\Core\Kernel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event dispatched to setup theme configuration at kernel request.
 */
class LocaleSubscriber implements EventSubscriberInterface
{
    private $kernel;

    /**
     * @param Kernel $kernel
     */
    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        /*
         * Locale subscriber has HIGH priority over Firewall and Routing
         */
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 70],
        ];
    }

    /**
     * After a controller has been matched. We need to inject current
     * Kernel instance and main DI container.
     *
     * @param RequestEvent $event
     */
    public function onKernelRequest(RequestEvent $event)
    {
        if ($event->isMasterRequest()) {
            $request = $event->getRequest();
            /*
             * Set default locale
             */
            if ($request->attributes->has('_locale') &&
                $request->attributes->get('_locale') !== '') {
                $locale = $request->attributes->get('_locale');
                $event->getRequest()->setLocale($locale);
                \Locale::setDefault($locale);
            } elseif (null !== $translation = $this->kernel->get('defaultTranslation')) {
                $shortLocale = $translation->getLocale();
                $event->getRequest()->setLocale($shortLocale);
                \Locale::setDefault($shortLocale);
            }
        }
    }
}
