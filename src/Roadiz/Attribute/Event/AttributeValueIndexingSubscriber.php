<?php
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Event;

use RZ\Roadiz\Attribute\Model\AttributeInterface;
use RZ\Roadiz\Attribute\Model\AttributeValueInterface;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesIndexingEvent;
use RZ\Roadiz\Core\SearchEngine\AbstractSolarium;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AttributeValueIndexingSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            NodesSourcesIndexingEvent::class => 'onNodeSourceIndexing',
        ];
    }

    public function onNodeSourceIndexing(NodesSourcesIndexingEvent $event)
    {
        if ($event->getNodeSource()->getNode()->getAttributeValues()->count() === 0) {
            return;
        }

        $associations = $event->getAssociations();
        $attributeValues = $event->getNodeSource()
                                ->getNode()
                                ->getAttributesValuesForTranslation($event->getNodeSource()->getTranslation());

        /** @var AttributeValueInterface $attributeValue */
        foreach ($attributeValues as $attributeValue) {
            if ($attributeValue->getAttribute()->isSearchable()) {
                $data = $attributeValue->getAttributeValueTranslation(
                    $event->getNodeSource()->getTranslation()
                )->getValue();
                if (null === $data) {
                    $data = $attributeValue->getAttributeValueTranslations()->first()->getValue();
                }
                if (null !== $data) {
                    switch ($attributeValue->getType()) {
                        case AttributeInterface::DATETIME_T:
                        case AttributeInterface::DATE_T:
                            if ($data instanceof \DateTime) {
                                $fieldName = $attributeValue->getAttribute()->getCode() . '_dt';
                                $associations[$fieldName] = $data->format('Y-m-d\TH:i:s');
                            }
                            break;
                        case AttributeInterface::STRING_T:
                            $fieldName = $attributeValue->getAttribute()->getCode();
                            /*
                            * Use locale to create field name
                            * with right language
                            */
                            if (in_array(
                                $event->getNodeSource()->getTranslation()->getLocale(),
                                AbstractSolarium::$availableLocalizedTextFields
                            )) {
                                $fieldName .= '_txt_' . $event->getNodeSource()->getTranslation()->getLocale();
                            } else {
                                $fieldName .= '_t';
                            }
                            /*
                             * Strip markdown syntax
                             */
                            $content = $event->getSolariumDocument() ?
                                $event->getSolariumDocument()->cleanTextContent($data) :
                                $data;
                            $associations[$fieldName] = $content;
                            $associations['collection_txt'][] = $content;
                            break;
                    }
                }
            }
        }

        $event->setAssociations($associations);
    }
}
