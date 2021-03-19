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
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\Workflow;

/**
 * Handle operations with nodes entities.
 */
class NodeHandler extends AbstractHandler
{
    /**
     * @var NodeChrootResolver
     */
    protected $chrootResolver;
    /**
     * @var Registry
     */
    private $registry;
    /**
     * @var null|Node
     */
    private $node;

    /**
     * NodeHandler constructor.
     *
     * @param ObjectManager      $objectManager
     * @param Registry           $registry
     * @param NodeChrootResolver $chrootResolver
     */
    final public function __construct(
        ObjectManager $objectManager,
        Registry $registry,
        NodeChrootResolver $chrootResolver
    ) {
        parent::__construct($objectManager);
        $this->registry = $registry;
        $this->chrootResolver = $chrootResolver;
    }

    /**
     * @return Node
     */
    public function getNode()
    {
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
    public function cleanCustomFormsFromField(NodeTypeField $field, $flush = true)
    {
        $nodesCustomForms = $this->objectManager
            ->getRepository(NodesCustomForms::class)
            ->findBy(['node' => $this->node, 'field' => $field]);

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
     * @param null|integer $position
     * @return $this
     */
    public function addCustomFormForField(CustomForm $customForm, NodeTypeField $field, $flush = true, $position = null)
    {
        $ncf = new NodesCustomForms($this->node, $customForm, $field);

        if (null === $position) {
            $latestPosition = $this->objectManager
                ->getRepository(NodesCustomForms::class)
                ->getLatestPosition($this->node, $field);
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
     * Get custom forms linked to current node for a given fieldname.
     *
     * @param string $fieldName Name of the node-type field
     * @return array
     */
    public function getCustomFormsFromFieldName($fieldName)
    {
        return $this->objectManager
            ->getRepository(CustomForm::class)
            ->findByNodeAndField(
                $this->node,
                $this->node->getNodeType()->getFieldByName($fieldName)
            );
    }

    /**
     * Remove every node to node associations for a given field.
     *
     * @param \RZ\Roadiz\Core\Entities\NodeTypeField $field
     *
     * @param bool $flush
     * @return $this
     */
    public function cleanNodesFromField(NodeTypeField $field, $flush = true)
    {
        $nodesToNodes = $this->objectManager
            ->getRepository(NodesToNodes::class)
            ->findBy(['nodeA' => $this->node, 'field' => $field]);

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
     * @param null|integer $position
     * @return $this
     */
    public function addNodeForField(Node $node, NodeTypeField $field, $flush = true, $position = null)
    {
        $ntn = new NodesToNodes($this->node, $node, $field);

        if (null === $position) {
            $latestPosition = $this->objectManager
                ->getRepository(NodesToNodes::class)
                ->getLatestPosition($this->node, $field);
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
    public function getNodesFromFieldName($fieldName)
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
    public function getReverseNodesFromFieldName($fieldName)
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
     * @return null|object|NodesSources
     */
    public function getNodeSourceByTranslation($translation)
    {
        return $this->objectManager
            ->getRepository(NodesSources::class)
            ->findOneBy(["node" => $this->node, "translation" => $translation]);
    }

    /**
     * Remove only current node children.
     *
     * @return $this
     */
    private function removeChildren()
    {
        /** @var Node $node */
        foreach ($this->node->getChildren() as $node) {
            $handler = new NodeHandler($this->objectManager, $this->registry, $this->chrootResolver);
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
        foreach ($this->node->getNodeSources() as $ns) {
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
        $this->objectManager->remove($this->node);

        return $this;
    }

    /**
     * @return Workflow
     */
    private function getWorkflow(): Workflow
    {
        return $this->registry->get($this->node);
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
        if ($workflow->can($this->node, 'delete')) {
            $workflow->apply($this->node, 'delete');
        }

        /** @var Node $node */
        foreach ($this->node->getChildren() as $node) {
            $handler = new NodeHandler($this->objectManager, $this->registry, $this->chrootResolver);
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
        if ($workflow->can($this->node, 'undelete')) {
            $workflow->apply($this->node, 'undelete');
        }

        /** @var Node $node */
        foreach ($this->node->getChildren() as $node) {
            $handler = new NodeHandler($this->objectManager, $this->registry, $this->chrootResolver);
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
        if ($workflow->can($this->node, 'publish')) {
            $workflow->apply($this->node, 'publish');
        }

        /** @var Node $node */
        foreach ($this->node->getChildren() as $node) {
            $handler = new NodeHandler($this->objectManager, $this->registry, $this->chrootResolver);
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
        if ($workflow->can($this->node, 'archive')) {
            $workflow->apply($this->node, 'archive');
        }

        /** @var Node $node */
        foreach ($this->node->getChildren() as $node) {
            $handler = new NodeHandler($this->objectManager, $this->registry, $this->chrootResolver);
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
    public function isRelatedToNewsletter()
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
    public function isRelatedToNode(Node $relative)
    {
        if ($this->node->getId() === $relative->getId()) {
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
     * @param TokenStorageInterface|null $tokenStorage
     * @return array<LeafInterface|Node>
     */
    public function getParents(TokenStorageInterface $tokenStorage = null)
    {
        if (null !== $this->node) {
            $parentsArray = [];
            $parent = $this->node->getParent();
            $user = null;
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
        return [];
    }

    /**
     * Clean position for current node siblings.
     *
     * Warning, this method does not flush.
     *
     * @param bool $setPositions
     * @return int Return the next position after the **last** node
     */
    public function cleanPositions($setPositions = true)
    {
        if ($this->node->getParent() !== null) {
            $parentHandler = new static($this->objectManager, $this->registry, $this->chrootResolver);
            $parentHandler->setNode($this->node->getParent());
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
     * @return int Return the next position after the **last** node
     */
    public function cleanChildrenPositions($setPositions = true)
    {
        /*
         * Force collection to sort on position
         */
        $sort = Criteria::create();
        $sort->orderBy([
            'position' => Criteria::ASC
        ]);

        $children = $this->node->getChildren()->matching($sort);
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
     * @return int Return the next position after the **last** node
     */
    public function cleanRootNodesPositions($setPositions = true)
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
     * @return array
     */
    public function getAllOffspringId()
    {
        return $this->getRepository()->findAllOffspringIdByNode($this->node);
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
        $this->node->setHome(true);
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
        $duplicator = new NodeDuplicator($this->node, $this->objectManager);
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
        array $criteria = null,
        array $order = null
    ) {
        if ($this->node->getPosition() <= 1) {
            return null;
        }
        if (null === $order) {
            $order = [];
        }

        if (null === $criteria) {
            $criteria = [];
        }

        $criteria['parent'] = $this->node->getParent();
        /*
         * Use < operator to get first previous nodeSource
         * even if it’s not the previous position index
         */
        $criteria['position'] = [
            '<',
            $this->node->getPosition(),
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
        array $criteria = null,
        array $order = null
    ) {
        if (null === $criteria) {
            $criteria = [];
        }
        if (null === $order) {
            $order = [];
        }

        $criteria['parent'] = $this->node->getParent();

        /*
         * Use > operator to get first next nodeSource
         * even if it’s not the next position index
         */
        $criteria['position'] = [
            '>',
            $this->node->getPosition(),
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
