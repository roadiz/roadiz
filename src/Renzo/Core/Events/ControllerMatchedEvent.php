<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file ControllerMatchedEvent.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Events;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\CMS\Controllers\AppController;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
/**
 * Event dispatched after a route has been matched.
 */
class ControllerMatchedEvent extends Event
{
    private $kernel;

    /**
     * @param RZ\Renzo\Core\Kernel $kernel
     */
    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }
    /**
     * After a controller has been matched. We need to inject current
     * Kernel instance and securityContext.
     *
     * @param \Symfony\Component\HttpKernel\Event\FilterControllerEvent $event
     */
    public function onControllerMatched(FilterControllerEvent $event)
    {
        $matchedCtrl = $event->getController()[0];

        /*
         * Inject current Kernel to the matched Controller
         */
        if ($matchedCtrl instanceof AppController) {

            $matchedCtrl->setKernel($this->kernel);
            $matchedCtrl->__init($this->kernel->getSecurityContext());
        }
    }
}
