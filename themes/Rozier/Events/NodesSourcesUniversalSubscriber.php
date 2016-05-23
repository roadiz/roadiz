<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
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
 * @file NodesSourcesUniversalSubscriber.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Events;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodesSourcesDocuments;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Events\FilterNodesSourcesEvent;
use RZ\Roadiz\Core\Events\NodesSourcesEvents;
use RZ\Roadiz\Core\Repositories\NodesSourcesRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class NodesSourcesUniversalSubscriber
 * @package Themes\Rozier\Events
 */
class NodesSourcesUniversalSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * NodesSourcesUniversalSubscriber constructor.
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            NodesSourcesEvents::NODE_SOURCE_UPDATED => 'duplicateUniversalContents',
        ];
    }

    /**
     * @param FilterNodesSourcesEvent $event
     */
    public function duplicateUniversalContents(FilterNodesSourcesEvent $event)
    {
        $source = $event->getNodeSource();
        /*
         * Only if source is default translation.
         * Non-default translation source should not contain universal fields.
         */
        if ($source->getTranslation()->isDefaultTranslation()) {
            $universalFields = $this->em
                ->getRepository('RZ\Roadiz\Core\Entities\NodeTypeField')
                ->findAllUniversal($source->getNode()->getNodeType());

            if (count($universalFields) > 0) {
                /** @var NodesSourcesRepository $repository */
                $repository = $this->em->getRepository('RZ\Roadiz\Core\Entities\NodesSources');
                $otherSources = $repository->findBy([
                    'node' => $source->getNode(),
                    'id' => ['!=', $source->getId()],
                ]);


                /** @var NodeTypeField $universalField */
                foreach ($universalFields as $universalField) {
                    /** @var NodesSources $otherSource */
                    foreach ($otherSources as $otherSource) {
                        if (!$universalField->isVirtual()) {
                            $this->duplicateNonVirtualField($source, $otherSource, $universalField);
                        } else {
                            if ($universalField->getType() == NodeTypeField::DOCUMENTS_T) {
                                $this->duplicateDocumentsField($source, $otherSource, $universalField);
                            }
                        }
                    }
                }

                $this->em->flush();
            }
        }
    }

    /**
     * @param NodesSources $universalSource
     * @param NodesSources $destSource
     * @param NodeTypeField $field
     */
    protected function duplicateNonVirtualField(NodesSources $universalSource, NodesSources $destSource, NodeTypeField $field)
    {
        $getter = $field->getGetterName();
        $setter = $field->getSetterName();

        $destSource->$setter($universalSource->$getter());
    }

    /**
     * @param NodesSources $universalSource
     * @param NodesSources $destSource
     * @param NodeTypeField $field
     */
    protected function duplicateDocumentsField(NodesSources $universalSource, NodesSources $destSource, NodeTypeField $field)
    {
        $newDocuments = $this->em
            ->getRepository('RZ\Roadiz\Core\Entities\NodesSourcesDocuments')
            ->findBy(['nodeSource' => $universalSource, 'field' => $field]);

        $formerDocuments = $this->em
            ->getRepository('RZ\Roadiz\Core\Entities\NodesSourcesDocuments')
            ->findBy(['nodeSource' => $destSource, 'field' => $field]);

        /* Delete former documents */
        if (count($formerDocuments) > 0) {
            foreach ($formerDocuments as $formerDocument) {
                $this->em->remove($formerDocument);
            }
        }
        /* Add new documents */
        if (count($newDocuments) > 0) {
            /** @var NodesSourcesDocuments $newDocument */
            $position = 1;
            foreach ($newDocuments as $newDocument) {
                $nsDoc = new NodesSourcesDocuments($destSource, $newDocument->getDocument(), $field);
                $nsDoc->setPosition($position);
                $position++;

                $this->em->persist($nsDoc);
            }
        }
    }
}
