<?php
/**
 * Copyright © 2019, Ambroise Maupate and Julien Blanchet
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
 * @file SerializationServiceProvider.php
 * @author Ambroise Maupate
 */
declare(strict_types=1);

namespace RZ\Roadiz\Core\Services;

use JMS\Serializer\Construction\UnserializeObjectConstructor;
use JMS\Serializer\EventDispatcher\EventDispatcher;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\Naming\SerializedNameAnnotationStrategy;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Attribute\Serializer\AttributeObjectConstructor;
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

class SerializationServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $container)
    {
        $container[SerializerBuilder::class] = function ($c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            return SerializerBuilder::create()
                ->setCacheDir($kernel->getCacheDir())
                ->setDebug($kernel->isDebug())
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
            //return new UnserializeObjectConstructor();
            return new ObjectConstructor();
        };

        $container[ChainDoctrineObjectConstructor::class] = function ($c) {
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

        $container['serializer.subscribers'] = function ($c) {
            return [];
        };

        /*
         * Alias with FQN
         */
        $container[Serializer::class] = function ($c) {
            return $c['serializer'];
        };

        /**
         * @param $c
         *
         * @return Serializer
         */
        $container['serializer'] = function ($c) {
            return $c[SerializerBuilder::class]->build();
        };
    }
}
