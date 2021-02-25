<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;
use RZ\Roadiz\Bag\LazyParameterBag;
use RZ\Roadiz\Core\Events\Role\PreCreatedRoleEvent;
use RZ\Roadiz\Core\Events\Role\PreDeletedRoleEvent;
use RZ\Roadiz\Core\Events\Role\PreUpdatedRoleEvent;
use RZ\Roadiz\Core\Events\Role\RoleEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RoleSubscriber implements EventSubscriberInterface
{
    /**
     * @var Cache|null
     */
    protected $cacheImplementation;

    /**
     * @var LazyParameterBag|null
     */
    protected $roles;

    /**
     * @param Cache|null $cacheImplementation
     * @param LazyParameterBag|null $roles
     */
    public function __construct(?Cache $cacheImplementation, ?LazyParameterBag $roles)
    {
        $this->cacheImplementation = $cacheImplementation;
        $this->roles = $roles;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            PreCreatedRoleEvent::class => 'onRoleChanged',
            PreUpdatedRoleEvent::class => 'onRoleChanged',
            PreDeletedRoleEvent::class => 'onRoleChanged',
        ];
    }

    public function onRoleChanged(RoleEvent $event)
    {
        // Clear result cache
        if (null !== $this->cacheImplementation && $this->cacheImplementation instanceof CacheProvider) {
            $this->cacheImplementation->deleteAll();
        }
        // Clear memory roles bag
        if (null !== $this->roles) {
            $this->roles->reset();
        }
    }
}
