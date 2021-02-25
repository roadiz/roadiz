<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Node;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ObjectManager;
use RZ\Roadiz\Core\Entities\AttributeValue;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodesSourcesDocuments;
use RZ\Roadiz\Core\Entities\NodesToNodes;

/**
 * Handle node duplication.
 */
final class NodeDuplicator
{
    private Node $originalNode;
    private ObjectManager $em;

    /**
     * @param Node $originalNode
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
     * Warning this method flush entityManager at its end.
     *
     * @return Node
     */
    public function duplicate(): Node
    {
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

    /**
     * Warning, do not do any FLUSH here to preserve transactional integrity.
     *
     * @param  Node $node
     * @return Node
     */
    private function doDuplicate(Node &$node): Node
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
                $doc = $nsDoc->getDocument();
                $nsDoc->setDocument($doc);
                $f = $nsDoc->getField();
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
    private function doDuplicateNodeRelations(Node $node): Node
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
