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
 * @file AttributeValueLifeCycleSubscriber.php
 * @author Ambroise Maupate
 *
 */
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Event;

use RZ\Roadiz\Attribute\Model\AttributeInterface;
use RZ\Roadiz\Attribute\Model\AttributeValueInterface;
use RZ\Roadiz\Core\Events\FilterSolariumNodeSourceEvent;
use RZ\Roadiz\Core\Events\NodesSourcesEvents;
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
            NodesSourcesEvents::NODE_SOURCE_INDEXING => 'onNodeSourceIndexing',
        ];
    }

    public function onNodeSourceIndexing(FilterSolariumNodeSourceEvent $event)
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
