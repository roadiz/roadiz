<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Handlers;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ObjectManager;
use RZ\Roadiz\Core\AbstractEntities\LeafInterface;
use RZ\Roadiz\Core\Authorization\Chroot\NodeChrootResolver;
use RZ\Roadiz\Core\Entities\CustomForm;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesCustomForms;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodesToNodes;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Repositories\NodeRepository;
use RZ\Roadiz\Utils\Node\NodeDuplicator;
use RZ\Roadiz\Utils\Node\NodeNamePolicyInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\Workflow;

/**
 * Handle operations with nodes entities.
 */
class NodeHandler extends AbstractHandler
{
    protected NodeChrootResolver $chrootResolver;
    private Registry $registry;
    private ?Node $node = null;
    private NodeNamePolicyInterface $nodeNamePolicy;

    /**
     * @param ObjectManager      $objectManager
     * @param Registry           $registry
     * @param NodeChrootResolver $chrootResolver
     * @param NodeNamePolicyInterface $nodeNamePolicy
     */
    final public function __construct(
        ObjectManager $objectManager,
        Registry $registry,
        NodeChrootResolver $chrootResolver,
        NodeNamePolicyInterface $nodeNamePolicy
    ) {
        parent::__construct($objectManager);
        $this->registry = $registry;
        $this->chrootResolver = $chrootResolver;
        $this->nodeNamePolicy = $nodeNamePolicy;
    }

    protected function createSelf(): self
    {
        return new static(
            $this->objectManager,
            $this->registry,
            $this->chrootResolver,
            $this->nodeNamePolicy
        );
    }

    /**
     * @return Node
     */
    public function getNode(): Node
    {
        if (null === $this->node) {
            throw new \BadMethodCallException('Node is null');
        }
        return $this->node;
    }

    /**
     * @param Node $node
     * @return NodeHandler
     */
    public function setNode(Node $node)
    {
        $this->node = $node;
        return $this;
    }

    /**
     * Remove every node to custom-forms associations for a given field.
     *
     * @param NodeTypeField $field
     * @param bool $flush
     * @return $this
     */
    public function cleanCustomFormsFromField(NodeTypeField $field, bool $flush = true)
    {
        $nodesCustomForms = $this->objectManager
            ->getRepository(NodesCustomForms::class)
            ->findBy(['node' => $this->getNode(), 'field' => $field]);

        foreach ($nodesCustomForms as $ncf) {
            $this->objectManager->remove($ncf);
        }

        if (true === $flush) {
            $this->objectManager->flush();
        }

        return $this;
    }

    /**
     * Add a node to current custom-forms for a given node-type field.
     *
     * @param CustomForm $customForm
     * @param NodeTypeField $field
     * @param bool $flush
     * @param null|float $position
     * @return $this
     */
    public function addCustomFormForField(
        CustomForm $customForm,
        NodeTypeField $field,
        bool $flush = true,
        ?float $position = null
    ) {
        $ncf = new NodesCustomForms($this->getNode(), $customForm, $field);

        if (null === $position) {
            $latestPosition = $this->objectManager
                ->getRepository(NodesCustomForms::class)
                ->getLatestPosition($this->getNode(), $field);
            $ncf->setPosition($latestPosition + 1);
        } else {
            $ncf->setPosition($position);
        }

        $this->objectManager->persist($ncf);

        if (true === $flush) {
            $this->objectManager->flush();
        }

        return $this;
    }

    /**
     * Get custom forms linked to current node for a given field name.
     *
     * @param string $fieldName Name of the node-type field
     * @return array
     */
    public function getCustomFormsFromFieldName(string $fieldName): array
    {
        return $this->objectManager
            ->getRepository(CustomForm::class)
            ->findByNodeAndField(
                $this->getNode(),
                $this->getNode()->getNodeType()->getFieldByName($fieldName)
            );
    }

    /**
     * Remove every node to node associations for a given field.
     *
     * @param NodeTypeField $field
     * @param bool $flush
     * @return $this
     */
    public function cleanNodesFromField(NodeTypeField $field, bool $flush = true)
    {
        $nodesToNodes = $this->objectManager
            ->getRepository(NodesToNodes::class)
            ->findBy(['nodeA' => $this->getNode(), 'field' => $field]);

        foreach ($nodesToNodes as $ntn) {
            $this->objectManager->remove($ntn);
        }

        if (true === $flush) {
            $this->objectManager->flush();
        }

        return $this;
    }

    /**
     * Add a node to current node for a given node-type field.
     *
     * @param Node $node
     * @param NodeTypeField $field
     * @param bool $flush
     * @param null|float $position
     * @return $this
     */
    public function addNodeForField(Node $node, NodeTypeField $field, bool $flush = true, ?float $position = null)
    {
        $ntn = new NodesToNodes($this->getNode(), $node, $field);

        if (null === $position) {
            $latestPosition = $this->objectManager
                ->getRepository(NodesToNodes::class)
                ->getLatestPosition($this->getNode(), $field);
            $ntn->setPosition($latestPosition + 1);
        } else {
            $ntn->setPosition($position);
        }

        $this->objectManager->persist($ntn);
        if (true === $flush) {
            $this->objectManager->flush();
        }

        return $this;
    }

    /**
     * Get nodes linked to current node for a given field name.
     *
     * @param string $fieldName Name of the node-type field
     * @return Node[]
     */
    public function getNodesFromFieldName(string $fieldName): array
    {
        $field = $this->getNode()->getNodeType()->getFieldByName($fieldName);
        if (null !== $field) {
            return $this->getRepository()
                ->findByNodeAndField(
                    $this->getNode(),
                    $field
                );
        }
        return [];
    }

    /**
     * Get nodes reversed-linked to current node for a given field name.
     *
     * @param string $fieldName Name of the node-type field
     * @return Node[]
     */
    public function getReverseNodesFromFieldName(string $fieldName): array
    {
        $field = $this->getNode()->getNodeType()->getFieldByName($fieldName);
        if (null !== $field) {
            return $this->getRepository()
                ->findByReverseNodeAndField(
                    $this->getNode(),
                    $field
                );
        }
        return [];
    }

    /**
     * Get node source by translation.
     *
     * @param Translation $translation
     *
     * @return null|NodesSources
     */
    public function getNodeSourceByTranslation($translation): ?NodesSources
    {
        return $this->objectManager
            ->getRepository(NodesSources::class)
            ->findOneBy(["node" => $this->getNode(), "translation" => $translation]);
    }

    /**
     * Remove only current node children.
     *
     * @return $this
     */
    private function removeChildren()
    {
        /** @var Node $node */
        foreach ($this->getNode()->getChildren() as $node) {
            $handler = $this->createSelf();
            $handler->setNode($node);
            $handler->removeWithChildrenAndAssociations();
        }

        return $this;
    }
    /**
     * Remove only current node associations.
     *
     * @return $this
     */
    public function removeAssociations()
    {
        /** @var NodesSources $ns */
        foreach ($this->getNode()->getNodeSources() as $ns) {
            $this->objectManager->remove($ns);
        }

        return $this;
    }
    /**
     * Remove current node with its children recursively and
     * its associations.
     *
     * This method DOES NOT flush objectManager
     *
     * @return $this
     */
    public function removeWithChildrenAndAssociations()
    {
        $this->removeChildren();
        $this->removeAssociations();
        $this->objectManager->remove($this->getNode());

        return $this;
    }

    /**
     * @return Workflow
     */
    private function getWorkflow(): Workflow
    {
        return $this->registry->get($this->getNode());
    }

    /**
     * Soft delete node and its children.
     *
     * **This method does not flush!**
     *
     * @return $this
     */
    public function softRemoveWithChildren()
    {
        $workflow = $this->getWorkflow();
        if ($workflow->can($this->getNode(), 'delete')) {
            $workflow->apply($this->getNode(), 'delete');
        }

        /** @var Node $node */
        foreach ($this->getNode()->getChildren() as $node) {
            $handler = $this->createSelf();
            $handler->setNode($node);
            $handler->softRemoveWithChildren();
        }

        return $this;
    }

    /**
     * Un-delete node and its children.
     *
     * **This method does not flush!**
     *
     * @return $this
     */
    public function softUnremoveWithChildren()
    {
        $workflow = $this->getWorkflow();
        if ($workflow->can($this->getNode(), 'undelete')) {
            $workflow->apply($this->getNode(), 'undelete');
        }

        /** @var Node $node */
        foreach ($this->getNode()->getChildren() as $node) {
            $handler = $this->createSelf();
            $handler->setNode($node);
            $handler->softUnremoveWithChildren();
        }

        return $this;
    }

    /**
     * Publish node and its children.
     *
     * **This method does not flush!**
     *
     * @return $this
     */
    public function publishWithChildren()
    {
        $workflow = $this->getWorkflow();
        if ($workflow->can($this->getNode(), 'publish')) {
            $workflow->apply($this->getNode(), 'publish');
        }

        /** @var Node $node */
        foreach ($this->getNode()->getChildren() as $node) {
            $handler = $this->createSelf();
            $handler->setNode($node);
            $handler->publishWithChildren();
        }
        return $this;
    }

    /**
     * Archive node and its children.
     *
     * **This method does not flush!**
     *
     * @return $this
     */
    public function archiveWithChildren()
    {
        $workflow = $this->getWorkflow();
        if ($workflow->can($this->getNode(), 'archive')) {
            $workflow->apply($this->getNode(), 'archive');
        }

        /** @var Node $node */
        foreach ($this->getNode()->getChildren() as $node) {
            $handler = $this->createSelf();
            $handler->setNode($node);
            $handler->archiveWithChildren();
        }

        return $this;
    }

    /**
     * Return if is in Newsletter Node.
     *
     * @deprecated Just here not to break themes.
     * @return bool
     */
    public function isRelatedToNewsletter(): bool
    {
        return false;
    }

    /**
     * Return if part of Node offspring.
     *
     * @param Node $relative
     *
     * @return bool
     */
    public function isRelatedToNode(Node $relative): bool
    {
        if ($this->getNode()->getId() === $relative->getId()) {
            return true;
        }

        $parents = $this->getParents();
        foreach ($parents as $parent) {
            if ($parent->getId() === $relative->getId()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Return every node’s parents
     *
     * @param TokenStorageInterface|null $tokenStorage
     * @return array<LeafInterface|Node>
     */
    public function getParents(?TokenStorageInterface $tokenStorage = null): array
    {
        $parentsArray = [];
        $parent = $this->getNode()->getParent();
        $chroot = null;

        if ($tokenStorage !== null) {
            $user = $tokenStorage->getToken()->getUser();
            /** @var Node|null $chroot */
            $chroot = $this->chrootResolver->getChroot($user);
        }

        while ($parent !== null && $parent !== $chroot) {
            $parentsArray[] = $parent;
            $parent = $parent->getParent();
        }

        return array_reverse($parentsArray);
    }

    /**
     * Clean position for current node siblings.
     *
     * Warning, this method does not flush.
     *
     * @param bool $setPositions
     * @return float Return the next position after the **last** node
     */
    public function cleanPositions(bool $setPositions = true): float
    {
        if ($this->getNode()->getParent() !== null) {
            $parentHandler = $this->createSelf();
            $parentHandler->setNode($this->getNode()->getParent());
            return $parentHandler->cleanChildrenPositions($setPositions);
        } else {
            return $this->cleanRootNodesPositions($setPositions);
        }
    }

    /**
     * Reset current node children positions.
     *
     * Warning, this method does not flush.
     *
     * @param bool $setPositions
     * @return float Return the next position after the **last** node
     */
    public function cleanChildrenPositions(bool $setPositions = true): float
    {
        /*
         * Force collection to sort on position
         */
        $sort = Criteria::create();
        $sort->orderBy([
            'position' => Criteria::ASC
        ]);

        $children = $this->getNode()->getChildren()->matching($sort);
        $i = 1;
        /** @var Node $child */
        foreach ($children as $child) {
            if ($setPositions) {
                $child->setPosition($i);
            }
            $i++;
        }

        return $i;
    }

    /**
     * Reset every root nodes positions.
     *
     * Warning, this method does not flush.
     *
     * @param bool $setPositions
     * @return float Return the next position after the **last** node
     */
    public function cleanRootNodesPositions(bool $setPositions = true): float
    {
        $nodes = $this->getRepository()
            ->setDisplayingNotPublishedNodes(true)
            ->findBy(['parent' => null], ['position' => 'ASC']);

        $i = 1;
        /** @var Node $child */
        foreach ($nodes as $child) {
            if ($setPositions) {
                $child->setPosition($i);
            }
            $i++;
        }

        return $i;
    }

    /**
     * Return all node offspring id.
     *
     * @return array
     */
    public function getAllOffspringId(): array
    {
        return $this->getRepository()->findAllOffspringIdByNode($this->getNode());
    }

    /**
     * Set current node as the Home node.
     *
     * @return $this
     */
    public function makeHome()
    {
        $defaults = $this->getRepository()
            ->setDisplayingNotPublishedNodes(true)
            ->findBy(['home' => true]);

        /** @var Node $default */
        foreach ($defaults as $default) {
            $default->setHome(false);
        }
        $this->getNode()->setHome(true);
        $this->objectManager->flush();

        return $this;
    }

    /**
     * Duplicate current node with all its children.
     *
     * @return Node
     * @deprecated Use NodeDuplicator::duplicate() instead.
     */
    public function duplicate()
    {
        $duplicator = new NodeDuplicator(
            $this->getNode(),
            $this->objectManager,
            $this->nodeNamePolicy
        );
        return $duplicator->duplicate();
    }

    /**
     * Get previous node from hierarchy.
     *
     * @param  array|null           $criteria
     * @param  array|null           $order
     *
     * @return Node|null
     */
    public function getPrevious(
        ?array $criteria = null,
        ?array $order = null
    ) {
        if ($this->getNode()->getPosition() <= 1) {
            return null;
        }
        if (null === $order) {
            $order = [];
        }

        if (null === $criteria) {
            $criteria = [];
        }

        $criteria['parent'] = $this->getNode()->getParent();
        /*
         * Use < operator to get first previous nodeSource
         * even if it’s not the previous position index
         */
        $criteria['position'] = [
            '<',
            $this->getNode()->getPosition(),
        ];

        $order['position'] = 'DESC';

        return $this->getRepository()->findOneBy(
            $criteria,
            $order
        );
    }

    /**
     * Get next node from hierarchy.
     *
     * @param  array|null $criteria
     * @param  array|null $order
     *
     * @return Node|null
     */
    public function getNext(
        ?array $criteria = null,
        ?array $order = null
    ) {
        if (null === $criteria) {
            $criteria = [];
        }
        if (null === $order) {
            $order = [];
        }

        $criteria['parent'] = $this->getNode()->getParent();

        /*
         * Use > operator to get first next nodeSource
         * even if it’s not the next position index
         */
        $criteria['position'] = [
            '>',
            $this->getNode()->getPosition(),
        ];
        $order['position'] = 'ASC';

        return $this->getRepository()
            ->findOneBy(
                $criteria,
                $order
            );
    }

    /**
     * @return NodeRepository
     */
    public function getRepository(): NodeRepository
    {
        return $this->objectManager->getRepository(Node::class);
    }
}
