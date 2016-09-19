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
use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodesToNodes;

/**
 * Handle node duplication.
 */
class NodeDuplicator
{
    private $em = null;
    private $originalNode = null;

    /**
     * NodeDuplicator constructor.
     *
     * @param Node          $originalNode
     * @param EntityManager $em
     */
    public function __construct(
        Node $originalNode,
        EntityManager $em
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

            $parent = $this->originalNode->getParent();
            $node = clone $this->originalNode;

            if ($this->em->contains($node)) {
                $this->em->clear($node);
            }

            if ($parent !== null) {
                $parent = $this->em->find('RZ\Roadiz\Core\Entities\Node', $parent->getId());
                $node->setParent($parent);
            }
            $node = $this->doDuplicate($node);
            $this->em->flush();
            $this->em->refresh($node);

            return $node;
        }

        throw new \RuntimeException('Node to be duplicated can’t be null.');
    }

    /**
     * Warning, do not do any FLUSH here to preserve transactionnal integrity.
     *
     * @param  Node $node
     * @return Node
     */
    private function doDuplicate(Node $node)
    {
        foreach ($node->getChildren() as $child) {
            $child->setParent($node);
            $this->doDuplicate($child);
        }

        /** @var NodesSources $nodeSource */
        foreach ($node->getNodeSources() as $nodeSource) {
            $this->em->persist($nodeSource);

            foreach ($nodeSource->getDocumentsByFields() as $nsDoc) {
                $nsDoc->setNodeSource($nodeSource);
                $doc = $this->em->merge($nsDoc->getDocument());
                $nsDoc->setDocument($doc);
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
         * Persist duplicated node
         */
        $this->em->persist($node);

        return $node;
    }

    /**
     * Duplicate Node to Node relationship.
     *
     * Warning, do not do any FLUSH here to preserve transactionnal integrity.
     *
     * @param Node   $node
     * @return Node
     */
    private function doDuplicateNodeRelations(Node $node)
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
