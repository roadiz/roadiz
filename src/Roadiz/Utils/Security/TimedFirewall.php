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
 * @file TimedFirewall.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Utils\Security;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Http\Firewall;
use Symfony\Component\Security\Http\FirewallMapInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class TimedFirewall extends Firewall
{
    protected $stopwatch;

    /**
     * Constructor.
     *
     * @param FirewallMapInterface     $map        A FirewallMapInterface instance
     * @param EventDispatcherInterface $dispatcher An EventDispatcherInterface instance
     * @param Stopwatch                $stopwatch
     */
    public function __construct(
        FirewallMapInterface $map,
        EventDispatcherInterface $dispatcher,
        Stopwatch $stopwatch
    ) {
        parent::__construct($map, $dispatcher);
        $this->stopwatch = $stopwatch;
    }

    /**
     * Handles security.
     *
     * @param GetResponseEvent $event An GetResponseEvent instance
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $this->stopwatch->start('firewallHandle');
        parent::onKernelRequest($event);
        $this->stopwatch->stop('firewallHandle');
    }

    public function onKernelFinishRequest(FinishRequestEvent $event)
    {
        $this->stopwatch->start('firewallFinish');
        parent::onKernelFinishRequest($event);
        $this->stopwatch->stop('firewallFinish');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        /*
         * MUST set firewall dispatch BEFORE routing
         * to be able to get preview mode working
         * based on User token.
         */
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 34],
            KernelEvents::FINISH_REQUEST => 'onKernelFinishRequest',
        ];
    }
}
