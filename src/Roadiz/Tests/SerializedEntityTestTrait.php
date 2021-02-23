<?php
declare(strict_types=1);

namespace RZ\Roadiz\Tests;

use JMS\Serializer\EventDispatcher\EventDispatcher;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\Subscriber\DoctrineProxySubscriber;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\Naming\SerializedNameAnnotationStrategy;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;

trait SerializedEntityTestTrait
{
    protected function getSerializer(): Serializer
    {
        $subscribers = [
            new DoctrineProxySubscriber(),
        ];
        return SerializerBuilder::create()
            ->setDebug(true)
            ->setPropertyNamingStrategy(
                new SerializedNameAnnotationStrategy(
                    new IdenticalPropertyNamingStrategy()
                )
            )
            ->addDefaultHandlers()
            ->configureListeners(function (EventDispatcher $dispatcher) use ($subscribers) {
                foreach ($subscribers as $subscriber) {
                    if ($subscriber instanceof EventSubscriberInterface) {
                        $dispatcher->addSubscriber($subscriber);
                    }
                }
            })->build();
    }
}
