<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Services;

use JMS\Serializer\EventDispatcher\EventDispatcher;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\Subscriber\DoctrineProxySubscriber;
use JMS\Serializer\Expression\ExpressionEvaluator;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\Naming\SerializedNameAnnotationStrategy;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Serializers\ObjectConstructor\ChainDoctrineObjectConstructor;
use RZ\Roadiz\Core\Serializers\ObjectConstructor\GroupObjectConstructor;
use RZ\Roadiz\Core\Serializers\ObjectConstructor\NodeObjectConstructor;
use RZ\Roadiz\Core\Serializers\ObjectConstructor\NodeTypeFieldObjectConstructor;
use RZ\Roadiz\Core\Serializers\ObjectConstructor\NodeTypeObjectConstructor;
use RZ\Roadiz\Core\Serializers\ObjectConstructor\ObjectConstructor;
use RZ\Roadiz\Core\Serializers\ObjectConstructor\RoleObjectConstructor;
use RZ\Roadiz\Core\Serializers\ObjectConstructor\SettingGroupObjectConstructor;
use RZ\Roadiz\Core\Serializers\ObjectConstructor\SettingObjectConstructor;
use RZ\Roadiz\Core\Serializers\ObjectConstructor\TagObjectConstructor;
use RZ\Roadiz\Core\Serializers\ObjectConstructor\TranslationObjectConstructor;
use RZ\Roadiz\Utils\CustomForm\CustormFormAnswerSerializer;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class SerializationServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $container)
    {
        $container[SerializerBuilder::class] = function (Container $c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            return SerializerBuilder::create()
                ->setCacheDir($kernel->getCacheDir())
                ->setDebug($kernel->isDebug())
                ->setExpressionEvaluator(new ExpressionEvaluator(new ExpressionLanguage()))
                ->setPropertyNamingStrategy(
                    new SerializedNameAnnotationStrategy(
                        new IdenticalPropertyNamingStrategy()
                    )
                )
                ->addDefaultHandlers()
                ->setObjectConstructor($c[ChainDoctrineObjectConstructor::class])
                ->configureListeners(function (EventDispatcher $dispatcher) use ($c) {
                    foreach ($c['serializer.subscribers'] as $subscriber) {
                        if ($subscriber instanceof EventSubscriberInterface) {
                            $dispatcher->addSubscriber($subscriber);
                        }
                    }
                });
        };

        $container['serializer.fallback_constructor'] = function () {
            return new ObjectConstructor();
        };

        $container[ChainDoctrineObjectConstructor::class] = function (Container $c) {
            $constructor = new ChainDoctrineObjectConstructor(
                $c['em'],
                $c['serializer.fallback_constructor']
            );
            $constructor->add(new TranslationObjectConstructor(
                $c['em'],
                $c['serializer.fallback_constructor']
            ))->add(new TagObjectConstructor(
                $c['em'],
                $c['serializer.fallback_constructor']
            ))->add(new NodeObjectConstructor(
                $c['em'],
                $c['serializer.fallback_constructor']
            ))->add(new NodeTypeObjectConstructor(
                $c['em'],
                $c['serializer.fallback_constructor']
            ))->add(new NodeTypeFieldObjectConstructor(
                $c['em'],
                $c['serializer.fallback_constructor']
            ))->add(new RoleObjectConstructor(
                $c['em'],
                $c['serializer.fallback_constructor']
            ))->add(new GroupObjectConstructor(
                $c['em'],
                $c['serializer.fallback_constructor']
            ))->add(new SettingObjectConstructor(
                $c['em'],
                $c['serializer.fallback_constructor']
            ))->add(new SettingGroupObjectConstructor(
                $c['em'],
                $c['serializer.fallback_constructor']
            ));
            return $constructor;
        };

        $container['serializer.subscribers'] = function () {
            return [
                new DoctrineProxySubscriber(),
            ];
        };

        /*
         * Alias with FQN
         */
        $container[Serializer::class] = function (Container $c) {
            return $c['serializer'];
        };

        /**
         * @param Container $c
         *
         * @return Serializer
         */
        $container['serializer'] = function (Container $c) {
            return $c[SerializerBuilder::class]->build();
        };

        $container[CustormFormAnswerSerializer::class] = function (Container $c) {
            return new CustormFormAnswerSerializer($c['router']);
        };
    }
}
