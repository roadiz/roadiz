<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file NodeHandler.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Handlers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\Entities\NodeTypeField;
use RZ\Renzo\Core\Entities\Translation;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Handle operations with nodes entities.
 */
class NodeHandler
{
    private $node = null;

    /**
     * @return RZ\Renzo\Core\Entities\Node
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * @param RZ\Renzo\Core\Entities\Node $node
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
     * Alias for NodesSourcesHandler::getUrl.
     *
     * @return string
     * @see RZ\Renzo\Core\Handlers\NodesSourcesHandler::getUrl
     */
    public function getUrl()
    {
        return $this->node
                    ->getNodeSources()
                    ->first()
                    ->getHandler()
                    ->getUrl();
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
     * @return ArrayCollection
     */
    public function getAvailableTranslations()
    {
        $query = Kernel::getService('em')
                        ->createQuery('
            SELECT t
            FROM RZ\Renzo\Core\Entities\Translation t
            INNER JOIN t.nodeSources ns
            INNER JOIN ns.node n
            WHERE n.id = :node_id')
                        ->setParameter('node_id', $this->node->getId());

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
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
            SELECT t.id FROM RZ\Renzo\Core\Entities\Node n
            INNER JOIN n.nodeSources ns
            INNER JOIN ns.translation t
            WHERE n.id = :node_id')
                        ->setParameter('node_id', $this->node->getId());

        try {
            $simpleArray = array();
            $complexArray = $query->getScalarResult();
            foreach ($complexArray as $subArray) {
                $simpleArray[] = $subArray['id'];
            }

            return $simpleArray;
        } catch (\Doctrine\ORM\NoResultException $e) {
            return array();
        }
    }

    /**
     * @return ArrayCollection
     */
    public function getUnavailableTranslations()
    {
        $query = Kernel::getService('em')
                        ->createQuery('SELECT t FROM RZ\Renzo\Core\Entities\Translation t
                                       WHERE t.id NOT IN (:translations_id)')
                        ->setParameter('translations_id', $this->getAvailableTranslationsId());

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @return array Array of Translation id
     */
    public function getUnavailableTranslationsId()
    {
        $query = Kernel::getService('em')
                        ->createQuery('SELECT t.id FROM RZ\Renzo\Core\Entities\Translation t
                                       WHERE t.id NOT IN (:translations_id)')
                        ->setParameter('translations_id', $this->getAvailableTranslationsId());

        try {
            $simpleArray = array();
            $complexArray = $query->getScalarResult();
            foreach ($complexArray as $subArray) {
                $simpleArray[] = $subArray['id'];
            }

            return $simpleArray;
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * Return every node’s parents
     * @return array
     */
    public function getParents()
    {
        $parentsArray = array();
        $parent = $this->node;

        do {
            $parent = $parent->getParent();
            if ($parent !== null) {
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
            ->getRepository('RZ\Renzo\Core\Entities\Node')
            ->findBy(array('parent' => null), array('position'=>'ASC'));

        $i = 1;
        foreach ($nodes as $child) {
            $child->setPosition($i);
            $i++;
        }

        Kernel::getService('em')->flush();

        return $i;
    }

    /**
     * Set current node as the Home node.
     *
     * @return $this
     */
    public function makeHome()
    {
        $defaults = Kernel::getService('em')
            ->getRepository('RZ\Renzo\Core\Entities\Node')
            ->findBy(array('home'=>true));

        foreach ($defaults as $default) {
            $default->setHome(false);
        }
        $this->node->setHome(true);
        Kernel::getService('em')->flush();

        return $this;
    }

    private function duplicateRec($node, $level) {
        $childrenArray = array();
        $sourceArray = array();
        $childs = new ArrayCollection($node->getChildren()->toArray());
        $node->getChildren()->clear();
        foreach ($childs as $child) {
            $childrenArray[] = $this->duplicateRec($child, $level + 1);
        }

        $nodeSources = new ArrayCollection($node->getNodeSources()->toArray());
        $node->getNodeSources()->clear();
        foreach ($nodeSources as $nodeSource) {

            $nodeSource->setNode(null);

            $tran = Kernel::getService('em')->merge($nodeSource->getTranslation());

            $nodeSource->setTranslation($tran);

            Kernel::getService('em')->persist($nodeSource);

            $nsdocs = $nodeSource->getDocumentsByFields();

            foreach ($nsdocs as $nsdoc) {
                $nsdoc->setNodeSource($nodeSource);
                $doc = Kernel::getService('em')->merge($nsdoc->getDocument());
                $nsdoc->setDocument($doc);
                $f = Kernel::getService('em')->merge($nsdoc->getField());
                $nsdoc->setField($f);
                Kernel::getService('em')->persist($nsdoc);
            }

            Kernel::getService('em')->flush();
            $sourceArray[] = $nodeSource;
        }
            //exit();
        $nodetype = Kernel::getService('em')->merge($node->getNodeType());

        $node->setNodeType($nodetype);

        $node->setParent(null);

        //$node->setNodeName($node->getNodeName()."-".uniqid());

        Kernel::getService('em')->persist($node);
        foreach ($childrenArray as $child) {
            $child->setParent($node);
        }
        foreach ($sourceArray as $source) {
            $source->setNode($node);
        }
        Kernel::getService('em')->flush();
        return $node;
    }

    public function duplicate() {
        Kernel::getService('em')->refresh($this->node);
        $parent = $this->node->getParent();
        $node = clone $this->node;
        Kernel::getService('em')->clear();
        $newNode = $this->duplicateRec($node, 0);
        if ($parent !== null) {
            $parent = Kernel::getService('em')->find("RZ\Renzo\Core\Entities\Node", $parent->getId());
            $newNode->setParent($parent);
        }
        Kernel::getService('em')->flush();
        Kernel::getService('em')->refresh($newNode);
        return $newNode;
    }
}
