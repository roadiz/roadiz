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
use Doctrine\ORM\NoResultException;
use RZ\Roadiz\Core\Entities\CustomForm;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesCustomForms;
use RZ\Roadiz\Core\Entities\NodesToNodes;

use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\Node\NodeDuplicator;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

/**
 * Handle operations with nodes entities.
 */
class NodeHandler
{
    private $node = null;

    /**
     * @return Node
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * @param Node $node
     *
     * @return $this
     */
    public function setNode($node)
    {
        $this->node = $node;

        return $this;
    }

    /**
     * Create a new node handler with node to handle.
     *
     * @param Node $node
     */
    public function __construct(Node $node)
    {
        $this->node = $node;
    }

    /**
     * Remove every node to custom-forms associations for a given field.
     *
     * @param NodeTypeField $field
     *
     * @return $this
     */
    public function cleanCustomFormsFromField(NodeTypeField $field)
    {
        $nodesCustomForms = Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\NodesCustomForms')
            ->findBy(['node' => $this->node, 'field' => $field]);

        foreach ($nodesCustomForms as $ncf) {
            Kernel::getService('em')->remove($ncf);
        }

        Kernel::getService('em')->flush();

        return $this;
    }

    /**
     * Add a node to current custom-forms for a given node-type field.
     *
     * @param CustomForm $customForm
     * @param NodeTypeField $field
     *
     * @return $this
     */
    public function addCustomFormForField(CustomForm $customForm, NodeTypeField $field)
    {
        $ncf = new NodesCustomForms($this->node, $customForm, $field);

        $latestPosition = Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\NodesCustomForms')
            ->getLatestPosition($this->node, $field);

        $ncf->setPosition($latestPosition + 1);

        Kernel::getService('em')->persist($ncf);
        Kernel::getService('em')->flush();

        return $this;
    }

    /**
     * Get custom forms linked to current node for a given fieldname.
     *
     * @param string $fieldName Name of the node-type field
     *
     * @return ArrayCollection Collection of nodes
     */
    public function getCustomFormsFromFieldName($fieldName)
    {
        return Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\CustomForm')
            ->findByNodeAndFieldName($this->node, $fieldName);
    }

    /**
     * Remove every node to node associations for a given field.
     *
     * @param \RZ\Roadiz\Core\Entities\NodeTypeField $field
     *
     * @return $this
     */
    public function cleanNodesFromField(NodeTypeField $field)
    {
        $nodesToNodes = Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\NodesToNodes')
            ->findBy(['nodeA' => $this->node, 'field' => $field]);

        foreach ($nodesToNodes as $ntn) {
            Kernel::getService('em')->remove($ntn);
        }

        Kernel::getService('em')->flush();

        return $this;
    }

    /**
     * Add a node to current node for a given node-type field.
     *
     * @param Node          $node
     * @param NodeTypeField $field
     *
     * @return $this
     */
    public function addNodeForField(Node $node, NodeTypeField $field)
    {
        $ntn = new NodesToNodes($this->node, $node, $field);

        $latestPosition = Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\NodesToNodes')
            ->getLatestPosition($this->node, $field);

        $ntn->setPosition($latestPosition + 1);

        Kernel::getService('em')->persist($ntn);
        Kernel::getService('em')->flush();

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
        return Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Node')
            ->findByNodeAndFieldName($this->node, $fieldName);
    }

    /**
     * Get nodes reversed-linked to current node for a given fieldname.
     *
     * @param string $fieldName Name of the node-type field
     *
     * @return ArrayCollection Collection of nodes
     */
    public function getReverseNodesFromFieldName($fieldName)
    {
        return Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Node')
            ->findByReverseNodeAndFieldName($this->node, $fieldName);
    }

    /**
     * Get node source by translation.
     *
     * @param \RZ\Roadiz\Core\Entities\Translation $translation
     *
     * @return \RZ\Roadiz\Core\Entities\NodesSources
     */
    public function getNodeSourceByTranslation($translation)
    {
        return Kernel::getService('em')
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
            Kernel::getService('em')->remove($ns);
        }

        return $this;
    }
    /**
     * Remove current node with its children recursively and
     * its associations.
     *
     * @return $this
     */
    public function removeWithChildrenAndAssociations()
    {
        $this->removeChildren();
        $this->removeAssociations();

        Kernel::getService('em')->remove($this->node);

        /*
         * Final flush
         */
        Kernel::getService('em')->flush();

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
        $this->node->setStatus(Node::PUBLISHED);

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
     * @return \RZ\Roadiz\Core\Entities\Translation[]|ArrayCollection
     */
    public function getAvailableTranslations()
    {
        $query = Kernel::getService('em')
            ->createQuery('
            SELECT t
            FROM RZ\Roadiz\Core\Entities\Translation t
            INNER JOIN t.nodeSources ns
            INNER JOIN ns.node n
            WHERE n.id = :node_id')
            ->setParameter('node_id', $this->node->getId());

        try {
            return $query->getResult();
        } catch (NoResultException $e) {
            return null;
        }
    }
    /**
     * @return array Array of Translation id
     */
    public function getAvailableTranslationsId()
    {
        $query = Kernel::getService('em')
            ->createQuery('
            SELECT t.id FROM RZ\Roadiz\Core\Entities\Node n
            INNER JOIN n.nodeSources ns
            INNER JOIN ns.translation t
            WHERE n.id = :node_id')
            ->setParameter('node_id', $this->node->getId());

        try {
            $simpleArray = [];
            $complexArray = $query->getScalarResult();
            foreach ($complexArray as $subArray) {
                $simpleArray[] = $subArray['id'];
            }

            return $simpleArray;
        } catch (NoResultException $e) {
            return [];
        }
    }

    /**
     * @return ArrayCollection
     */
    public function getUnavailableTranslations()
    {
        $query = Kernel::getService('em')
            ->createQuery('SELECT t FROM RZ\Roadiz\Core\Entities\Translation t
                                       WHERE t.id NOT IN (:translations_id)')
            ->setParameter('translations_id', $this->getAvailableTranslationsId());

        try {
            return $query->getResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * @return array Array of Translation id
     */
    public function getUnavailableTranslationsId()
    {
        $query = Kernel::getService('em')
            ->createQuery('SELECT t.id FROM RZ\Roadiz\Core\Entities\Translation t
                                       WHERE t.id NOT IN (:translations_id)')
            ->setParameter('translations_id', $this->getAvailableTranslationsId());

        try {
            $simpleArray = [];
            $complexArray = $query->getScalarResult();
            foreach ($complexArray as $subArray) {
                $simpleArray[] = $subArray['id'];
            }

            return $simpleArray;
        } catch (NoResultException $e) {
            return null;
        }
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
        if ($this->node == $relative) {
            return true;
        }

        $parents = $this->getParents();
        foreach ($parents as $parent) {
            if ($parent == $relative) {
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
     * @return int Return the next position after the **last** node
     */
    public function cleanPositions()
    {
        if ($this->node->getParent() !== null) {
            return $this->node->getParent()->getHandler()->cleanChildrenPositions();
        } else {
            return static::cleanRootNodesPositions();
        }
    }

    /**
     * Reset current node children positions.
     *
     * @return int Return the next position after the **last** node
     */
    public function cleanChildrenPositions()
    {
        $children = $this->node->getChildren();
        $i = 1;
        foreach ($children as $child) {
            $child->setPosition($i);
            $i++;
        }

        Kernel::getService('em')->flush();

        return $i;
    }

    /**
     * Reset every root nodes positions.
     *
     * @return int Return the next position after the **last** node
     */
    public static function cleanRootNodesPositions()
    {
        $nodes = Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Node')
            ->findBy(['parent' => null], ['position' => 'ASC']);

        $i = 1;
        foreach ($nodes as $child) {
            $child->setPosition($i);
            $i++;
        }

        Kernel::getService('em')->flush();

        return $i;
    }

    /**
     * return all node offspring id
     *
     * @return ArrayCollection
     */
    public function getAllOffspringId()
    {
        return Kernel::getService('em')->getRepository("RZ\Roadiz\Core\Entities\Node")
            ->findAllOffspringIdByNode($this->node);
    }

    /**
     * Set current node as the Home node.
     *
     * @return $this
     */
    public function makeHome()
    {
        $defaults = Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Node')
            ->findBy(['home' => true]);

        foreach ($defaults as $default) {
            $default->setHome(false);
        }
        $this->node->setHome(true);
        Kernel::getService('em')->flush();

        return $this;
    }

    /**
     * Duplicate current node with all its children.
     *
     * @return Node
     */
    public function duplicate()
    {
        $duplicator = new NodeDuplicator($this->node, Kernel::getService('em'));

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

        return Kernel::getService('em')
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

        return Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Node')
            ->findOneBy(
                $criteria,
                $order,
                $authorizationChecker,
                $preview
            );
    }
}
