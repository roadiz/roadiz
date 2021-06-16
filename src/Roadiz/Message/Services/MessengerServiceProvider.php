<?php
declare(strict_types=1);

namespace RZ\Roadiz\Message\Services;

use Doctrine\Persistence\ManagerRegistry;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Message\GuzzleRequestMessage;
use RZ\Roadiz\Message\Handler\GuzzleRequestMessageHandler;
use RZ\Roadiz\Utils\Log\LoggerFactory;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransportFactory;
use Symfony\Component\Messenger\Bridge\Doctrine\Transport\DoctrineTransportFactory;
use Symfony\Component\Messenger\Bridge\Redis\Transport\RedisTransportFactory;
use Symfony\Component\Messenger\Command\ConsumeMessagesCommand;
use Symfony\Component\Messenger\Command\DebugCommand;
use Symfony\Component\Messenger\Handler\HandlerDescriptor;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\Handler\HandlersLocatorInterface;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\AddBusNameStampMiddleware;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\Middleware\RouterContextMiddleware;
use Symfony\Component\Messenger\Middleware\SendMessageMiddleware;
use Symfony\Component\Messenger\RoutableMessageBus;
use Symfony\Component\Messenger\Transport\Sender\SendersLocator;
use Symfony\Component\Messenger\Transport\Sender\SendersLocatorInterface;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\Serialization\Serializer;
use Symfony\Component\Messenger\Transport\Sync\SyncTransportFactory;
use Symfony\Component\Messenger\Transport\TransportFactory;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;

final class MessengerServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $pimple)
    {
        $pimple['logger.messenger'] = function (Container $c) {
            /** @var LoggerFactory $factory */
            $factory = $c[LoggerFactory::class];
            return $factory->createLogger('messenger', 'messenger');
        };

        $pimple['messenger.serializer'] = function (Container $c) {
            return new PhpSerializer();
        };

        $pimple['messenger.transports'] = function (Container $c) {
            /** @var TransportFactoryInterface $transportFactory */
            $transportFactory = $c[TransportFactoryInterface::class];
            /** @var Serializer $serializer */
            $serializer = $c['messenger.serializer'];

            return array_map(function (array $config) use ($transportFactory, $serializer) {
                return $transportFactory->createTransport($config['dsn'], $config['options'], $serializer);
            }, $c['config']['messenger']['transports']);
        };

        $pimple['messenger.default_transport'] = function (Container $c) {
            /** @var TransportFactoryInterface $transportFactory */
            $transportFactory = $c[TransportFactoryInterface::class];
            $dsn = $c['config']['messenger']['transports']['default']['dsn'] ?? 'sync://';
            $options = $c['config']['messenger']['transports']['default']['options'] ?? [];
            return $transportFactory->createTransport($dsn, $options, $c['messenger.serializer']);
        };

        $pimple['messenger.default_bus'] = function (Container $c) {
            return new MessageBus($c['messenger.default_bus.middlewares']);
        };
        $pimple['messenger.default_bus.middlewares'] = function (Container $c) {
            return [
                new AddBusNameStampMiddleware('messenger.default_bus'),
                $c[RouterContextMiddleware::class],
                // these middleware MUST be last
                $c[SendMessageMiddleware::class],
                $c[HandleMessageMiddleware::class],
            ];
        };

        $pimple['messenger.handlers'] = function (Container $c) {
            return [
                GuzzleRequestMessage::class => [
                    $c[GuzzleRequestMessageHandler::class]
                ],
            ];
        };

        $pimple['messenger.senders'] = function (Container $c) {
            return [
                GuzzleRequestMessage::class => [
                    'messenger.default_transport'
                ]
            ];
        };

        $pimple[TransportFactoryInterface::class] = function (Container $c) {
            return new TransportFactory([
                new SyncTransportFactory($c[MessageBusInterface::class]),
                new DoctrineTransportFactory($c[ManagerRegistry::class]),
                new AmqpTransportFactory(),
                new RedisTransportFactory(),
                new RedisTransportFactory(),
            ]);
        };

        $pimple[SendersLocatorInterface::class] = function (Container $c) {
            return new SendersLocator($c['messenger.senders'], new \Pimple\Psr11\Container($c));
        };

        $pimple[RouterContextMiddleware::class] = function (Container $c) {
            return new RouterContextMiddleware($c['router']);
        };

        $pimple[HandleMessageMiddleware::class] = function (Container $c) {
            $middleware = new HandleMessageMiddleware($c[HandlersLocatorInterface::class]);
            $middleware->setLogger($c['logger.messenger']);
            return $middleware;
        };

        $pimple[SendMessageMiddleware::class] = function (Container $c) {
            $middleware = new SendMessageMiddleware($c[SendersLocatorInterface::class], $c['proxy.dispatcher']);
            $middleware->setLogger($c['logger.messenger']);
            return $middleware;
        };

        $pimple[HandlersLocatorInterface::class] = function (Container $c) {
            return new HandlersLocator($c['messenger.handlers']);
        };

        $pimple[MessageBusInterface::class] = function (Container $c) {
            return new RoutableMessageBus(
                new \Pimple\Psr11\Container($c),
                $c['messenger.default_bus']
            );
        };

        $pimple->extend('console.commands', function (array $commands, Container $c) {
            return array_merge($commands, [
                new DebugCommand([
                    'messenger.default_bus' => $c['messenger.default_bus'],
                ]),
                new ConsumeMessagesCommand(
                    $c[MessageBusInterface::class],
                    new \Pimple\Psr11\Container($c),
                    $c['proxy.dispatcher'],
                    $c['logger.messenger'],
                    [
                        'messenger.default_transport'
                    ]
                ),
            ]);
        });

        /*
         * Handlers
         */
        /*
         * Allow HTTP requests to be performed async
         */
        $pimple[GuzzleRequestMessageHandler::class] = function (Container $c) {
            return new HandlerDescriptor(
                new GuzzleRequestMessageHandler(null, $c['logger.messenger']),
                [
                    'handles' => GuzzleRequestMessage::class,
                ]
            );
        };
    }
}
