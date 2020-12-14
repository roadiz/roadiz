<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use Gedmo\Loggable\LoggableListener;
use Pimple\Container;
use RZ\Roadiz\Core\ContainerAwareInterface;
use RZ\Roadiz\Core\ContainerAwareTrait;
use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\Utils\Doctrine\Loggable\UserLoggableListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class LoggableUsernameSubscriber implements EventSubscriberInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

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
        return [
            KernelEvents::REQUEST => ['onRequest', 33],
        ];
    }

    /**
     * @param RequestEvent $event
     */
    public function onRequest(RequestEvent $event)
    {
        if ($event->isMasterRequest()) {
            /** @var TokenStorage $tokenStorage */
            $tokenStorage = $this->get('securityTokenStorage');

            if ($tokenStorage->getToken() && $tokenStorage->getToken()->getUsername() !== '') {
                $loggableListener = $this->get(LoggableListener::class);
                if ($loggableListener instanceof UserLoggableListener &&
                    $tokenStorage->getToken()->getUser() instanceof User) {
                    $loggableListener->setUser($tokenStorage->getToken()->getUser());
                } else {
                    $loggableListener->setUsername($tokenStorage->getToken()->getUsername());
                }
            }
        }
    }
}
