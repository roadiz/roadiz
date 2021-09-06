<?php
declare(strict_types=1);

namespace Themes\DefaultTheme\Serialization;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use Pimple\Container;
use RZ\Roadiz\Core\ContainerAwareInterface;
use RZ\Roadiz\Core\ContainerAwareTrait;
use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Utils\UrlGenerators\DocumentUrlGeneratorInterface;

class DocumentUriSubscriber implements EventSubscriberInterface, ContainerAwareInterface
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
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [[
            'event' => 'serializer.post_serialize',
            'method' => 'onPostSerialize',
            'class' => DocumentInterface::class,
        ]];
    }

    public function onPostSerialize(ObjectEvent $event)
    {
        $document = $event->getObject();
        $visitor = $event->getVisitor();
        $context = $event->getContext();

        if ($context->hasAttribute('groups') &&
            in_array('urls', $context->getAttribute('groups'))) {
            /** @var DocumentUrlGeneratorInterface $urlGenerator */
            $urlGenerator = $this->get('document.url_generator')->setDocument($document);

            if ($document instanceof DocumentInterface) {
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
