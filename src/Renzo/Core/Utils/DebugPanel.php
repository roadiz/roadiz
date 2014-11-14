<?php
/**
 * Copyright © 2014, REZO ZERO
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
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file DebugPanel.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Utils;


use RZ\Renzo\Core\Kernel;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

use Symfony\Bridge\Twig\Extension\RoutingExtension;
use Symfony\Bridge\Twig\Form\TwigRenderer;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 * Event subscriber which append a debug console after any HTML output.
 */
class DebugPanel implements EventSubscriberInterface
{
    private $twig = null;

    /**
     * {@inheritdoc}
     */
    public function getTwig()
    {
        return $this->twig;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array('kernel.response' => 'onKernelResponse');
    }

    /**
     * {@inheritdoc}
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $response = $event->getResponse();
        //
        if (false !== strpos($response->getContent(), '<!-- ##debug_panel## -->')) {
            $this->initializeTwig();
            $content = str_replace('<!-- ##debug_panel## -->', $this->getDebugView(), $response->getContent());
            $response->setContent($content);
            $event->setResponse($response);

        } elseif (false !== strpos($response->getContent(), '</body>')) {

            $this->initializeTwig();
            $content = str_replace('</body>', $this->getDebugView()."</body>", $response->getContent());
            $response->setContent($content);
            $event->setResponse($response);
        }
    }

    private function getDebugView()
    {
        Kernel::getService('stopwatch')->stopSection('runtime');

        $assignation = array(
            'stopwatch'=>Kernel::getService('stopwatch')
        );

        return $this->getTwig()->render('debug-panel.html.twig', $assignation);
    }

    /**
     * {@inheritdoc}
     */
    private function initializeTwig()
    {
        $cacheDir = RENZO_ROOT.'/cache/twig_cache';

        $loader = new \Twig_Loader_Filesystem(array(
            RENZO_ROOT.'/src/Renzo/CMS/Resources/views', // Theme templates
        ));
        $this->twig = new \Twig_Environment($loader, array(
            'debug' => Kernel::getInstance()->isDebug(),
            'cache' => $cacheDir
        ));

        //RoutingExtension
        $this->twig->addExtension(
            new RoutingExtension(Kernel::getService('urlGenerator'))
        );

        return $this;
    }
}
