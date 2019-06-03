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
 * @file DocumentUriSubscriber.php
 * @author Ambroise Maupate
 *
 */
declare(strict_types=1);

namespace Themes\DefaultTheme\Serialization;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use Pimple\Container;
use RZ\Roadiz\Core\ContainerAwareInterface;
use RZ\Roadiz\Core\ContainerAwareTrait;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Utils\UrlGenerators\DocumentUrlGenerator;

class DocumentUriSubscriber implements EventSubscriberInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * DocumentUriSubscriber constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [[
            'event' => 'serializer.post_serialize',
            'method' => 'onPostSerialize',
            'class' => Document::class,
        ]];
    }

    public function onPostSerialize(ObjectEvent $event)
    {
        $document = $event->getObject();
        $visitor = $event->getVisitor();
        $context = $event->getContext();

        if ($context->hasAttribute('groups') &&
            in_array('urls', $context->getAttribute('groups'))) {
            /** @var DocumentUrlGenerator $urlGenerator */
            $urlGenerator = $this->get('document.url_generator')->setDocument($document);

            if ($document instanceof Document) {
                if ($visitor instanceof SerializationVisitorInterface) {
                    $visitor->visitProperty(new StaticPropertyMetadata('array', 'urls', []), [
                        'original' => $urlGenerator->setOptions(['noProcess' => true])->getUrl(true),
                        'small' => $urlGenerator->setOptions(['width' => 300])->getUrl(true),
                        'medium' => $urlGenerator->setOptions(['width' => 700])->getUrl(true),
                        'large' => $urlGenerator->setOptions(['width' => 1500])->getUrl(true)
                    ]);
                }
            }
        }
    }
}
