<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
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
 * @file NodeHandler.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Handlers;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use RZ\Roadiz\Core\Entities\CustomForm;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesCustomForms;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodesToNodes;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\Node\NodeDuplicator;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

/**
 * Handle operations with nodes entities.
 */
class NodeHandler extends AbstractHandler
{
    /** @var null|Node  */
    private $node;

    /** @var AuthorizationChecker */
    protected $authorizationChecker;

    /** @var bool  */
    protected $isPreview = false;

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
     * Create a new node handler with node to handle.
     *
     * @param Node|null $node
     */
    public function __construct(Node $node = null)
    {
        parent::__construct();
        $this->node = $node;
        $this->authorizationChecker = Kernel::getService('securityAuthorizationChecker');
        $this->isPreview = Kernel::getInstance()->isPreview();
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
        $nodesCustomForms = $this->entityManager
            ->getRepository('RZ\Roadiz\Core\Entities\NodesCustomForms')
            ->findBy(['node' => $this->node, 'field' => $field]);

        foreach ($nodesCustomForms as $ncf) {
            $this->entityManager->remove($ncf);
        }

        if (true === $flush) {
            $this->entityManager->flush();
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
            $latestPosition = $this->entityManager
                ->getRepository('RZ\Roadiz\Core\Entities\NodesCustomForms')
                ->getLatestPosition($this->node, $field);
            $ncf->setPosition($latestPosition + 1);
        } else {
            $ncf->setPosition($position);
        }

        $this->entityManager->persist($ncf);

        if (true === $flush) {
            $this->entityManager->flush();
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
        return $this->entityManager
            ->getRepository('RZ\Roadiz\Core\Entities\CustomForm')
            ->findByNodeAndFieldName($this->node, $fieldName);
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
        $nodesToNodes = $this->entityManager
            ->getRepository('RZ\Roadiz\Core\Entities\NodesToNodes')
            ->findBy(['nodeA' => $this->node, 'field' => $field]);

        foreach ($nodesToNodes as $ntn) {
            $this->entityManager->remove($ntn);
        }

        if (true === $flush) {
            $this->entityManager->flush();
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
            $latestPosition = $this->entityManager
                ->getRepository('RZ\Roadiz\Core\Entities\NodesToNodes')
                ->getLatestPosition($this->node, $field);
            $ntn->setPosition($latestPosition + 1);
        } else {
            $ntn->setPosition($position);
        }

        $this->entityManager->persist($ntn);
        if (true === $flush) {
            $this->entityManager->flush();
        }

        return $this;
    }

    /**
     * Get nodes linked to current node for a given fieldname.
     *
     * @param string $fieldName Name of the node-type field
     *
     * @return ArrayCollection Collection of nodes
     */
    public function getNodesFromFieldName($fieldName)
    {
        return $this->entityManager
            ->getRepository('RZ\Roadiz\Core\Entities\Node')
            ->findByNodeAndFieldName(
                $this->node,
                $fieldName,
                $this->authorizationChecker,
                $this->isPreview
            );
    }

    /**
     * Get nodes reversed-linked to current node for a given fieldname.
     *
     * @param string $fieldName Name of the node-type field
     * @return ArrayCollection Collection of nodes
     */
    public function getReverseNodesFromFieldName($fieldName)
    {
        return $this->entityManager
            ->getRepository('RZ\Roadiz\Core\Entities\Node')
            ->findByReverseNodeAndFieldName(
                $this->node,
                $fieldName,
                $this->authorizationChecker,
                $this->isPreview
            );
    }

    /**
     * Get node source by translation.
     *
     * @param Translation $translation
     *
     * @return NodesSources|null
     */
    public function getNodeSourceByTranslation($translation)
    {
        return $this->entityManager
            ->getRepository('RZ\Roadiz\Core\Entities\NodesSources')
            ->findOneBy(["node" => $this->node, "translation" => $translation]);
    }

    /**
     * Remove only current node children.
     *
     * @return $this
     */
    private function removeChildren()
    {
        foreach ($this->node->getChildren() as $node) {
            $node->getHandler()->removeWithChildrenAndAssociations();
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
        foreach ($this->node->getNodeSources() as $ns) {
            $this->entityManager->remove($ns);
        }

        return $this;
    }
    /**
     * Remove current node with its children recursively and
     * its associations.
     *
     * This method DOES NOT flush entityManager
     *
     * @return $this
     */
    public function removeWithChildrenAndAssociations()
    {
        $this->removeChildren();
        $this->removeAssociations();

        $this->entityManager->remove($this->node);

        return $this;
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
        $this->node->setStatus(Node::DELETED);

        foreach ($this->node->getChildren() as $node) {
            $node->getHandler()->softRemoveWithChildren();
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
        $this->node->setStatus(Node::PENDING);

        foreach ($this->node->getChildren() as $node) {
            $node->getHandler()->softUnremoveWithChildren();
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
        /*
         * Publish only if node is Draft or pending
         * NOT deleted nor archived.
         */
        if ($this->node->getStatus() < Node::PUBLISHED) {
            $this->node->setStatus(Node::PUBLISHED);
        }

        foreach ($this->node->getChildren() as $node) {
            $node->getHandler()->publishWithChildren();
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
        $this->node->setStatus(Node::ARCHIVED);

        foreach ($this->node->getChildren() as $node) {
            $node->getHandler()->archiveWithChildren();
        }

        return $this;
    }

    /**
     * Alias for NodeRepository::findAvailableTranslationForNode.
     *
     * @return Translation[]
     */
    public function getAvailableTranslations()
    {
        return $this->entityManager->getRepository('RZ\Roadiz\Core\Entities\Node')
            ->findAvailableTranslationForNode($this->node);
    }
    /**
     * Alias for NodeRepository::findAvailableTranslationIdForNode.
     *
     * @return array Array of Translation id
     */
    public function getAvailableTranslationsId()
    {
        return $this->entityManager->getRepository('RZ\Roadiz\Core\Entities\Node')
                                       ->findAvailableTranslationIdForNode($this->node);
    }

    /**
     * Alias for NodeRepository::findUnavailableTranslationForNode.
     *
     * @return array
     */
    public function getUnavailableTranslations()
    {
        return $this->entityManager->getRepository('RZ\Roadiz\Core\Entities\Node')
            ->findUnavailableTranslationForNode($this->node);
    }

    /**
     * Alias for NodeRepository::findUnavailableTranslationIdForNode.
     *
     * @return array Array of Translation id
     */
    public function findUnavailableTranslationIdForNode()
    {
        return $this->entityManager->getRepository('RZ\Roadiz\Core\Entities\Node')
            ->findUnavailableTranslationIdForNode($this->node);
    }

    /**
     * Return if is in Newsletter Node.
     *
     * @return bool
     */
    public function isRelatedToNewsletter()
    {
        if ($this->node->getNodeType()->isNewsletterType()) {
            return true;
        }

        $parents = $this->getParents();
        foreach ($parents as $parent) {
            if ($parent->getNodeType()->isNewsletterType()) {
                return true;
            }
        }
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
     *
     * @return array
     */
    public function getParents(TokenStorageInterface $tokenStorage = null)
    {
        $parentsArray = [];
        $parent = $this->node;
        $user = null;

        if ($tokenStorage !== null) {
            $user = $tokenStorage->getToken()->getUser();
        }

        do {
            $parent = $parent->getParent();
            if ($parent !== null && !($user !== null && $parent == $user->getChroot())) {
                $parentsArray[] = $parent;
            } else {
                break;
            };
        } while ($parent !== null);

        return array_reverse($parentsArray);
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
            $parentHandler = new NodeHandler();
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
        $nodes = $this->entityManager
            ->getRepository('RZ\Roadiz\Core\Entities\Node')
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
        return $this->entityManager->getRepository('RZ\Roadiz\Core\Entities\Node')
            ->findAllOffspringIdByNode($this->node);
    }

    /**
     * Set current node as the Home node.
     *
     * @return $this
     */
    public function makeHome()
    {
        $defaults = $this->entityManager
            ->getRepository('RZ\Roadiz\Core\Entities\Node')
            ->findBy(['home' => true]);

        /** @var Node $default */
        foreach ($defaults as $default) {
            $default->setHome(false);
        }
        $this->node->setHome(true);
        $this->entityManager->flush();

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
        $duplicator = new NodeDuplicator($this->node, $this->entityManager);
        return $duplicator->duplicate();
    }

    /**
     * Get previous node from hierarchy.
     *
     * @param  array|null           $criteria
     * @param  array|null           $order
     * @param  AuthorizationChecker|null $authorizationChecker
     * @param  boolean $preview
     *
     * @return \RZ\Roadiz\Core\Entities\Node
     */
    public function getPrevious(
        array $criteria = null,
        array $order = null,
        AuthorizationChecker $authorizationChecker = null,
        $preview = false
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

        return $this->entityManager
            ->getRepository('RZ\Roadiz\Core\Entities\Node')
            ->findOneBy(
                $criteria,
                $order,
                $authorizationChecker,
                $preview
            );
    }

    /**
     * Get next node from hierarchy.
     *
     * @param  array|null           $criteria
     * @param  array|null           $order
     * @param  AuthorizationChecker|null $authorizationChecker
     * @param  boolean $preview
     *
     * @return \RZ\Roadiz\Core\Entities\Node
     */
    public function getNext(
        array $criteria = null,
        array $order = null,
        AuthorizationChecker $authorizationChecker = null,
        $preview = false
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

        return $this->entityManager
            ->getRepository('RZ\Roadiz\Core\Entities\Node')
            ->findOneBy(
                $criteria,
                $order,
                $authorizationChecker,
                $preview
            );
    }
}
