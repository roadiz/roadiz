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
 * @file NodeDuplicator.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Utils\Node;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use RZ\Roadiz\Core\Entities\AttributeValue;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodesSourcesDocuments;
use RZ\Roadiz\Core\Entities\NodesToNodes;
use RZ\Roadiz\Core\Entities\NodeTypeField;

/**
 * Handle node duplication.
 */
class NodeDuplicator
{
    private $em = null;

    /**
     * @var null|Node
     */
    private $originalNode = null;

    /**
     * NodeDuplicator constructor.
     *
     * @param Node          $originalNode
     * @param ObjectManager $em
     */
    public function __construct(
        Node $originalNode,
        ObjectManager $em
    ) {
        $this->em = $em;
        $this->originalNode = $originalNode;
    }

    /**
     *
     * Warning this method flush entityManager at its end.
     * @return Node
     */
    public function duplicate()
    {
        if (null !== $this->originalNode) {
            $this->em->refresh($this->originalNode);

            if ($this->originalNode->isLocked()) {
                throw new \RuntimeException('Locked node cannot be duplicated.');
            }

            $parent = $this->originalNode->getParent();
            $node = clone $this->originalNode;

            if ($this->em->contains($node)) {
                $this->em->clear($node);
            }

            if ($parent !== null) {
                /** @var Node $parent */
                $parent = $this->em->find(Node::class, $parent->getId());
                $node->setParent($parent);
            }
            // Demote cloned node to draft
            $node->setStatus(Node::DRAFT);

            $node = $this->doDuplicate($node);
            $this->em->flush();
            $this->em->refresh($node);

            return $node;
        }

        throw new \RuntimeException('Node to be duplicated can’t be null.');
    }

    /**
     * Warning, do not do any FLUSH here to preserve transactional integrity.
     *
     * @param  Node $node
     * @return Node
     */
    private function doDuplicate(Node &$node)
    {
        /** @var Node $child */
        foreach ($node->getChildren() as $child) {
            $child->setParent($node);
            $this->doDuplicate($child);
        }

        /** @var NodesSources $nodeSource */
        foreach ($node->getNodeSources() as $nodeSource) {
            $this->em->persist($nodeSource);

            /** @var NodesSourcesDocuments $nsDoc */
            foreach ($nodeSource->getDocumentsByFields() as $nsDoc) {
                $nsDoc->setNodeSource($nodeSource);
                /** @var Document $doc */
                $doc = $this->em->merge($nsDoc->getDocument());
                $nsDoc->setDocument($doc);
                /** @var NodeTypeField $f */
                $f = $this->em->merge($nsDoc->getField());
                $nsDoc->setField($f);
                $this->em->persist($nsDoc);
            }
        }

        /*
         * Duplicate Node to Node relationship
         */
        $this->doDuplicateNodeRelations($node);
        /*
         * Duplicate Node attributes values
         */
        /** @var AttributeValue $attributeValue */
        foreach ($node->getAttributeValues() as $attributeValue) {
            $this->em->persist($attributeValue);
            foreach ($attributeValue->getAttributeValueTranslations() as $attributeValueTranslation) {
                $this->em->persist($attributeValueTranslation);
            }
        }

        /*
         * Persist duplicated node
         */
        $this->em->persist($node);

        return $node;
    }

    /**
     * Duplicate Node to Node relationship.
     *
     * Warning, do not do any FLUSH here to preserve transactional integrity.
     *
     * @param Node $node
     * @return Node
     */
    private function doDuplicateNodeRelations(Node &$node)
    {
        $nodeRelations = new ArrayCollection($node->getBNodes()->toArray());
        foreach ($nodeRelations as $position => $nodeRelation) {
            $ntn = new NodesToNodes($node, $nodeRelation->getNodeB(), $nodeRelation->getField());
            $ntn->setPosition($position);
            $this->em->persist($ntn);
        }

        return $node;
    }
}
