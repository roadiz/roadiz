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
 * Except as contained in this notice, the name of the roadiz shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file AttributesServiceProvider.php
 * @author Ambroise Maupate
 *
 */
declare(strict_types=1);

namespace RZ\Roadiz\Attribute;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Attribute\Event\AttributeValueIndexingSubscriber;
use RZ\Roadiz\Attribute\Event\AttributeValueLifeCycleSubscriber;
use RZ\Roadiz\Attribute\Twig\AttributesExtension;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\Translator;

class AttributesServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $container)
    {
        $container->extend('dispatcher', function (EventDispatcherInterface $dispatcher) {
            $dispatcher->addSubscriber(new AttributeValueIndexingSubscriber());
            return $dispatcher;
        });

        $container->extend('twig.extensions', function ($extensions, $c) {
            $extensions->add(new AttributesExtension());
            return $extensions;
        });

        $container->extend('em.eventSubscribers', function (array $subscribers, Container $c) {
            array_push($subscribers, new AttributeValueLifeCycleSubscriber($c));
            return $subscribers;
        });

        $container->extend('translator', function (Translator $translator) {
            $translator->addResource(
                'xlf',
                dirname(__FILE__) . '/Resources/translations/messages.en.xlf',
                'en'
            );
            $translator->addResource(
                'xlf',
                dirname(__FILE__) . '/Resources/translations/messages.fr.xlf',
                'fr'
            );
            return $translator;
        });
    }
}
