<?php
/**
 * Copyright © 2014, REZO ZERO
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
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file NodeHandler.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Handlers;

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodesToNodes;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Entities\Translation;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Handle operations with nodes entities.
 */
class NodeHandler
{
    private $node = null;

    /**
     * @return RZ\Roadiz\Core\Entities\Node
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Node $node
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
                ->findBy(array('nodeA'=>$this->node, 'field'=>$field));

        foreach ($nodesToNodes as $ntn) {
            Kernel::getService('em')->remove($ntn);
            Kernel::getService('em')->flush();
        }

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
     * Alias for NodesSourcesHandler::getUrl.
     *
     * @return string
     * @see RZ\Roadiz\Core\Handlers\NodesSourcesHandler::getUrl
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
        $ping = Kernel::getInstance()->pingSolrServer();

        foreach ($this->node->getNodeSources() as $ns) {
            // Update Solr Search engine if setup
            if (true === $ping) {
                $solrSource = new \RZ\Roadiz\Core\SearchEngine\SolariumNodeSource(
                    $ns,
                    Kernel::getService('solr')
                );
                $solrSource->getDocumentFromIndex();
                $solrSource->cleanAndCommit();
            }

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
            FROM RZ\Roadiz\Core\Entities\Translation t
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
            SELECT t.id FROM RZ\Roadiz\Core\Entities\Node n
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
                        ->createQuery('SELECT t FROM RZ\Roadiz\Core\Entities\Translation t
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
                        ->createQuery('SELECT t.id FROM RZ\Roadiz\Core\Entities\Translation t
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
            ->getRepository('RZ\Roadiz\Core\Entities\Node')
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
            ->getRepository('RZ\Roadiz\Core\Entities\Node')
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

    public function duplicate()
    {
        Kernel::getService('em')->refresh($this->node);

        $parent = $this->node->getParent();
        $node = clone $this->node;
        Kernel::getService('em')->clear();

        $newNode = $this->duplicateRec($node, 0);
        if ($parent !== null) {
            $parent = Kernel::getService('em')->find("RZ\Roadiz\Core\Entities\Node", $parent->getId());
            $newNode->setParent($parent);
        }
        Kernel::getService('em')->flush();
        Kernel::getService('em')->refresh($newNode);

        return $newNode;
    }
}
