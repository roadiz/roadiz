<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Security;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Http\Firewall as BaseFirewall;

class Firewall extends BaseFirewall
{
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
