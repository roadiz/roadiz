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
 * @file PreviewBarSubscriber.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Core\Events;

use Pimple\Container;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class PreviewBarSubscriber implements EventSubscriberInterface
{
    protected $container = null;

    /**
     * DebugPanel constructor.
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
            KernelEvents::RESPONSE => ['onKernelResponse', -128]
        ];
    }

    /**
     * @param FilterResponseEvent $event
     *
     * @return bool
     */
    protected function supports(FilterResponseEvent $event)
    {
        $response = $event->getResponse();
        if ($event->isMasterRequest() &&
            $this->container['kernel']->isPreview() &&
            $response->getStatusCode() === Response::HTTP_OK &&
            false !== strpos($response->headers->get('Content-Type'), 'text/html')) {
            return true;
        }

        return false;
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if ($this->supports($event)) {
            $response = $event->getResponse();

            if (false !== strpos($response->getContent(), '</body>') &&
                false !== strpos($response->getContent(), '</head>')) {
                $content = str_replace(
                    '</head>',
                    "<style>#roadiz-preview-bar { font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Oxygen-Sans, Ubuntu, Cantarell, \"Helvetica Neue\", Helvetica, Arial, sans-serif; position: fixed; display: inline-flex; align-items: center; font-size: 9px; padding: 6px 10px 5px; bottom: 0; left: 1em; background-color: #ffe200; color: #923f00; border-radius: 3px 3px 0 0; text-transform: uppercase; letter-spacing: 0.005em; z-index: 9999;} #roadiz-preview-bar svg { width: 14px; margin-right: 5px;}</style></head>",
                    $response->getContent()
                );
                $content = str_replace(
                    '</body>',
                    "<div id=\"roadiz-preview-bar\"><svg aria-hidden=\"true\" data-prefix=\"fas\" data-icon=\"exclamation-triangle\" class=\"svg-inline--fa fa-exclamation-triangle fa-w-18\" role=\"img\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 576 512\"><path fill=\"currentColor\" d=\"M569.517 440.013C587.975 472.007 564.806 512 527.94 512H48.054c-36.937 0-59.999-40.055-41.577-71.987L246.423 23.985c18.467-32.009 64.72-31.951 83.154 0l239.94 416.028zM288 354c-25.405 0-46 20.595-46 46s20.595 46 46 46 46-20.595 46-46-20.595-46-46-46zm-43.673-165.346l7.418 136c.347 6.364 5.609 11.346 11.982 11.346h48.546c6.373 0 11.635-4.982 11.982-11.346l7.418-136c.375-6.874-5.098-12.654-11.982-12.654h-63.383c-6.884 0-12.356 5.78-11.981 12.654z\"></path></svg>" . $this->container['translator']->trans('preview') . "</div></body>",
                    $content
                );
                $response->setContent($content);
                $event->setResponse($response);
            }
        }
    }
}
