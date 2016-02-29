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
 * @file NodesSourcesHandler.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Handlers;

use RZ\Roadiz\Core\Bags\SettingsBag;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodesSourcesDocuments;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

/**
 * Handle operations with node-sources entities.
 */
class NodesSourcesHandler
{
    protected $nodeSource;
    protected $parentNodeSource = null;
    protected $parentsNodeSources = null;

    /**
     * Create a new node-source handler with node-source to handle.
     *
     * @param \RZ\Roadiz\Core\Entities\NodesSources $nodeSource
     */
    public function __construct($nodeSource)
    {
        $this->nodeSource = $nodeSource;
    }

    /**
     * @return \RZ\Roadiz\Core\Entities\NodesSources
     */
    public function getParentNodeSource()
    {
        return $this->parentNodeSource;
    }

    /**
     * @param \RZ\Roadiz\Core\Entities\NodesSources $newparentNodeSource
     */
    public function setParentNodeSource($newparentNodeSource)
    {
        $this->parentNodeSource = $newparentNodeSource;

        return $this;
    }

    /**
     * Remove every node-source documents associations for a given field.
     *
     * @param \RZ\Roadiz\Core\Entities\NodeTypeField $field
     *
     * @return $this
     */
    public function cleanDocumentsFromField(NodeTypeField $field)
    {
        $nsDocuments = Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\NodesSourcesDocuments')
            ->findBy(['nodeSource' => $this->nodeSource, 'field' => $field]);

        if (count($nsDocuments) > 0) {
            foreach ($nsDocuments as $nsDoc) {
                Kernel::getService('em')->remove($nsDoc);
            }
            Kernel::getService('em')->flush();
        }

        return $this;
    }

    /**
     * Add a document to current node-source for a given node-type field.
     *
     * @param Document      $document
     * @param NodeTypeField $field
     *
     * @return $this
     */
    public function addDocumentForField(Document $document, NodeTypeField $field)
    {
        $nsDoc = new NodesSourcesDocuments($this->nodeSource, $document, $field);

        $latestPosition = Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\NodesSourcesDocuments')
            ->getLatestPosition($this->nodeSource, $field);

        $nsDoc->setPosition($latestPosition + 1);

        Kernel::getService('em')->persist($nsDoc);
        Kernel::getService('em')->flush();

        return $this;
    }

    /**
     * Get documents linked to current node-source for a given fieldname.
     *
     * @param string $fieldName Name of the node-type field
     *
     * @return ArrayCollection Collection of documents
     */
    public function getDocumentsFromFieldName($fieldName)
    {
        return Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Document')
            ->findByNodeSourceAndFieldName($this->nodeSource, $fieldName);
    }

    /**
     * Get a string describing uniquely the curent nodeSource.
     *
     * Can be the urlAlias or the nodeName
     *
     * @return string
     */
    public function getIdentifier()
    {
        if (count($this->nodeSource->getUrlAliases()) > 0) {
            $urlalias = $this->nodeSource->getUrlAliases()->first();

            if ($urlalias !== null) {
                return $urlalias->getAlias();
            }
        }

        return $this->nodeSource->getNode()->getNodeName();
    }

    /**
     * Get parent node-source to get the current translation.
     *
     * @return NodesSources
     */
    public function getParent()
    {
        if (null === $this->parentNodeSource) {
            $this->parentNodeSource = Kernel::getService('em')
                 ->getRepository('RZ\Roadiz\Core\Entities\NodesSources')
                 ->findParent($this->nodeSource);
        }

        return $this->parentNodeSource;
    }

    /**
     * Get every nodeSources parents from direct parent to farest ancestor.
     *
     * @param  array                $criteria
     * @param  AuthorizationChecker|null $authorizationChecker
     * @param  boolean $preview
     *
     * @return array
     */
    public function getParents(
        array $criteria = null,
        AuthorizationChecker $authorizationChecker = null,
        $preview = false
    ) {
        if (null === $this->parentsNodeSources) {
            $this->parentsNodeSources = [];

            if (null === $criteria) {
                $criteria = [];
            }

            $parent = $this->nodeSource;

            while (null !== $parent) {
                $criteria = array_merge(
                    $criteria,
                    [
                        'node' => $parent->getNode()->getParent(),
                        'translation' => $this->nodeSource->getTranslation(),
                    ]
                );
                $currentParent = Kernel::getService('em')
                    ->getRepository('RZ\Roadiz\Core\Entities\NodesSources')
                    ->findOneBy(
                        $criteria,
                        [],
                        $authorizationChecker,
                        $preview
                    );

                if (null !== $currentParent) {
                    $this->parentsNodeSources[] = $currentParent;
                }

                $parent = $currentParent;
            }
        }

        return $this->parentsNodeSources;
    }

    /**
     * Get children nodes sources to lock with current translation.
     *
     * @param array|null                                      $criteria Additionnal criteria
     * @param array|null                                      $order Non default ordering
     * @param AuthorizationChecker|null   $authorizationChecker
     * @param boolean $preview
     *
     * @return ArrayCollection NodesSources collection
     */
    public function getChildren(
        array $criteria = null,
        array $order = null,
        AuthorizationChecker $authorizationChecker = null,
        $preview = false
    ) {

        $defaultCrit = [
            'node.parent' => $this->nodeSource->getNode(),
            'node.status' => ['<=', Node::PUBLISHED],
            'translation' => $this->nodeSource->getTranslation(),
        ];

        if (null !== $order) {
            $defaultOrder = $order;
        } else {
            $defaultOrder = [
                'node.position' => 'ASC',
            ];
        }

        if (null !== $criteria) {
            $defaultCrit = array_merge($defaultCrit, $criteria);
        }

        if (null === $authorizationChecker) {
            $authorizationChecker = Kernel::getService('securityAuthorizationChecker');
        }

        return Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\NodesSources')
            ->findBy(
                $defaultCrit,
                $defaultOrder,
                null,
                null,
                $authorizationChecker,
                $preview
            );
    }

    /**
     * Get first node-source among current node-source children.
     *
     * Get non-newsletter nodes-sources by default.
     *
     * @param  array|null           $criteria
     * @param  array|null           $order
     * @param  AuthorizationChecker|null $authorizationChecker
     * @param boolean $preview
     *
     * @return \RZ\Roadiz\Core\Entities\NodesSources
     */
    public function getFirstChild(
        array $criteria = null,
        array $order = null,
        AuthorizationChecker $authorizationChecker = null,
        $preview = false
    ) {
        $defaultCrit = [
            'node.parent' => $this->nodeSource->getNode(),
            'node.status' => ['<=', Node::PUBLISHED],
            'translation' => $this->nodeSource->getTranslation(),
            'node.nodeType.newsletterType' => false,
        ];

        if (null !== $order) {
            $defaultOrder = $order;
        } else {
            $defaultOrder = [
                'node.position' => 'ASC',
            ];
        }

        if (null !== $criteria) {
            $defaultCrit = array_merge($defaultCrit, $criteria);
        }

        if (null === $authorizationChecker) {
            $authorizationChecker = Kernel::getService('securityAuthorizationChecker');
        }

        return Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\NodesSources')
            ->findOneBy(
                $defaultCrit,
                $defaultOrder,
                $authorizationChecker,
                $preview
            );
    }
    /**
     * Get last node-source among current node-source children.
     *
     * Get non-newsletter nodes-sources by default.
     *
     * @param  array|null           $criteria
     * @param  array|null           $order
     * @param  AuthorizationChecker|null $authorizationChecker
     * @param boolean $preview
     *
     * @return \RZ\Roadiz\Core\Entities\NodesSources
     */
    public function getLastChild(
        array $criteria = null,
        array $order = null,
        AuthorizationChecker $authorizationChecker = null,
        $preview = false
    ) {
        $defaultCrit = [
            'node.parent' => $this->nodeSource->getNode(),
            'node.status' => ['<=', Node::PUBLISHED],
            'translation' => $this->nodeSource->getTranslation(),
            'node.nodeType.newsletterType' => false,
        ];

        if (null !== $order) {
            $defaultOrder = $order;
        } else {
            $defaultOrder = [
                'node.position' => 'DESC',
            ];
        }

        if (null !== $criteria) {
            $defaultCrit = array_merge($defaultCrit, $criteria);
        }

        if (null === $authorizationChecker) {
            $authorizationChecker = Kernel::getService('securityAuthorizationChecker');
        }

        return Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\NodesSources')
            ->findOneBy(
                $defaultCrit,
                $defaultOrder,
                $authorizationChecker,
                $preview
            );
    }

    /**
     * Get first node-source in the same parent as current node-source.
     *
     * @param  array|null           $criteria
     * @param  array|null           $order
     * @param  AuthorizationChecker|null $authorizationChecker
     * @param boolean $preview
     *
     * @return \RZ\Roadiz\Core\Entities\NodesSources
     */
    public function getFirstSibling(
        array $criteria = null,
        array $order = null,
        AuthorizationChecker $authorizationChecker = null,
        $preview = false
    ) {
        if (null !== $this->getParent()) {
            return $this->getParent()->getHandler()->getFirstChild($criteria, $order, $authorizationChecker);
        } else {
            $criteria['node.parent'] = null;
            return $this->getFirstChild($criteria, $order, $authorizationChecker);
        }
    }

    /**
     * Get last node-source in the same parent as current node-source.
     *
     * Get non-newsletter nodes-sources by default.
     *
     * @param  array|null           $criteria
     * @param  array|null           $order
     * @param  AuthorizationChecker|null $authorizationChecker
     * @param boolean $preview
     *
     * @return \RZ\Roadiz\Core\Entities\NodesSources
     */
    public function getLastSibling(
        array $criteria = null,
        array $order = null,
        AuthorizationChecker $authorizationChecker = null,
        $preview = false
    ) {
        if (null !== $this->getParent()) {
            return $this->getParent()->getHandler()->getLastChild($criteria, $order, $authorizationChecker);
        } else {
            $criteria['node.parent'] = null;
            return $this->getLastChild($criteria, $order, $authorizationChecker);
        }
    }

    /**
     * Get previous node-source from hierarchy.
     *
     * Get non-newsletter nodes-sources by default.
     *
     * @param  array|null           $criteria
     * @param  array|null           $order
     * @param  AuthorizationChecker|null $authorizationChecker
     * @param boolean $preview
     *
     * @return \RZ\Roadiz\Core\Entities\NodesSources
     */
    public function getPrevious(
        array $criteria = null,
        array $order = null,
        AuthorizationChecker $authorizationChecker = null,
        $preview = false
    ) {
        if ($this->nodeSource->getNode()->getPosition() <= 1) {
            return null;
        }

        $defaultCrit = [
            'node.nodeType.newsletterType' => false,
            /*
             * Use < operator to get first next nodeSource
             * even if it’s not the next position index
             */
            'node.position' => [
                '<',
                $this->nodeSource
                     ->getNode()
                     ->getPosition(),
            ],
            'node.parent' => $this->nodeSource->getNode()->getParent(),
            'translation' => $this->nodeSource->getTranslation(),
        ];
        if (null !== $criteria) {
            $defaultCrit = array_merge($defaultCrit, $criteria);
        }

        if (null === $order) {
            $order = [];
        }

        $order['node.position'] = 'DESC';

        return Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\NodesSources')
            ->findOneBy(
                $defaultCrit,
                $order,
                $authorizationChecker,
                $preview
            );
    }

    /**
     * Get next node-source from hierarchy.
     *
     * Get non-newsletter nodes-sources by default.
     *
     * @param  array|null           $criteria
     * @param  array|null           $order
     * @param  AuthorizationChecker|null $authorizationChecker
     * @param boolean $preview
     *
     * @return \RZ\Roadiz\Core\Entities\NodesSources
     */
    public function getNext(
        array $criteria = null,
        array $order = null,
        AuthorizationChecker $authorizationChecker = null,
        $preview = false
    ) {
        $defaultCrit = [
            'node.nodeType.newsletterType' => false,
            /*
             * Use > operator to get first next nodeSource
             * even if it’s not the next position index
             */
            'node.position' => [
                '>',
                $this->nodeSource
                     ->getNode()
                     ->getPosition(),
            ],
            'node.parent' => $this->nodeSource->getNode()->getParent(),
            'translation' => $this->nodeSource->getTranslation(),
        ];
        if (null !== $criteria) {
            $defaultCrit = array_merge($defaultCrit, $criteria);
        }

        if (null === $order) {
            $order = [];
        }

        $order['node.position'] = 'ASC';

        return Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\NodesSources')
            ->findOneBy(
                $defaultCrit,
                $order,
                $authorizationChecker,
                $preview
            );
    }

    /**
     * Get node tags with current source translation.
     *
     * @return ArrayCollection
     */
    public function getTags()
    {
        $tags = Kernel::getService('tagApi')->getBy([
            "nodes" => $this->nodeSource->getNode(),
            "translation" => $this->nodeSource->getTranslation(),
        ]);

        return $tags;
    }

    /**
     * Get current node-source SEO data.
     *
     * @return array
     */
    public function getSEO()
    {
        return [
            'title' => ($this->nodeSource->getMetaTitle() != "") ?
            $this->nodeSource->getMetaTitle() :
            $this->nodeSource->getTitle() . ' – ' . SettingsBag::get('site_name'),
            'description' => ($this->nodeSource->getMetaDescription() != "") ?
            $this->nodeSource->getMetaDescription() :
            $this->nodeSource->getTitle() . ', ' . SettingsBag::get('seo_description'),
            'keywords' => $this->nodeSource->getMetaKeywords(),
        ];
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
            ->findByNodeAndFieldNameAndTranslation(
                $this->nodeSource->getNode(),
                $fieldName,
                $this->nodeSource->getTranslation()
            );
    }

    /**
     * Get nodes which own a reference to current node for a given fieldname.
     *
     * @param string $fieldName Name of the node-type field
     *
     * @return ArrayCollection Collection of nodes
     */
    public function getReverseNodesFromFieldName($fieldName)
    {
        return Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Node')
            ->findByReverseNodeAndFieldNameAndTranslation(
                $this->nodeSource->getNode(),
                $fieldName,
                $this->nodeSource->getTranslation()
            );
    }
}
