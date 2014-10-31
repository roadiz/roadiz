<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file NodesSourcesHandler.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Handlers;

use RZ\Renzo\Core\Entities\NodesSources;
use RZ\Renzo\Core\Entities\Document;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\NodesSourcesDocuments;
use RZ\Renzo\Core\Entities\NodeTypeField;
use RZ\Renzo\Core\Kernel;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * Handle operations with node-sources entities.
 */
class NodesSourcesHandler
{
    protected $nodeSource;

    /**
     * Create a new node-source handler with node-source to handle.
     *
     * @param RZ\Renzo\Core\Entities\NodesSources $nodeSource
     */
    public function __construct($nodeSource)
    {
        $this->nodeSource = $nodeSource;
    }


    /**
     * Remove every node-source documents associations for a given field.
     *
     * @param \RZ\Renzo\Core\Entities\NodeTypeField $field
     *
     * @return $this
     */
    public function cleanDocumentsFromField(NodeTypeField $field)
    {
        $nsDocuments = Kernel::getService('em')
                ->getRepository('RZ\Renzo\Core\Entities\NodesSourcesDocuments')
                ->findBy(array('nodeSource'=>$this->nodeSource, 'field'=>$field));

        foreach ($nsDocuments as $nsDoc) {
            Kernel::getService('em')->remove($nsDoc);
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
                ->getRepository('RZ\Renzo\Core\Entities\NodesSourcesDocuments')
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
                ->getRepository('RZ\Renzo\Core\Entities\Document')
                ->findByNodeSourceAndFieldName($this->nodeSource, $fieldName);
    }

    /**
     * @return string Current node-source URL
     */
    public function getUrl()
    {
        if ($this->nodeSource->getNode()->isHome()) {

            if ($this->nodeSource->getTranslation()->isDefaultTranslation()) {
                return Kernel::getInstance()->getRequest()->getBaseUrl();
            } else {
                return Kernel::getInstance()->getRequest()->getBaseUrl() .
                        '/' . $this->nodeSource->getTranslation()->getLocale();
            }
        }

        $urlTokens = array();
        $urlTokens[] = $this->getIdentifier();

        $parent = $this->getParent();
        if ($parent !== null &&
            !$parent->getNode()->isHome()) {

            do {
                $handler = $parent->getHandler();
                $urlTokens[] = $handler->getIdentifier();
                $parent = $parent->getHandler()->getParent();
            } while ($parent !== null && !$parent->getNode()->isHome());
        }

        /*
         * If using node-name, we must use shortLocale when current
         * translation is not the default one.
         */
        if ($urlTokens[0] == $this->nodeSource->getNode()->getNodeName() &&
            !$this->nodeSource->getTranslation()->isDefaultTranslation()) {
            $urlTokens[] = $this->nodeSource->getTranslation()->getLocale();
        }

        $urlTokens[] = Kernel::getInstance()->getRequest()->getBaseUrl();
        $urlTokens = array_reverse($urlTokens);

        return implode('/', $urlTokens);
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
        $urlalias = $this->nodeSource->getUrlAliases()->first();
        if ($urlalias != null) {
            return $urlalias->getAlias();
        } else {
            return $this->nodeSource->getNode()->getNodeName();
        }
    }

    /**
     * Get parent node-source to get the current translation.
     *
     * @return NodesSources
     */
    public function getParent()
    {
        $parent = $this->nodeSource->getNode()->getParent();
        if ($parent !== null) {
            $query = Kernel::getService('em')
                            ->createQuery('SELECT ns FROM RZ\Renzo\Core\Entities\NodesSources ns
                                           WHERE ns.node = :node
                                           AND ns.translation = :translation')
                            ->setParameter('node', $parent)
                            ->setParameter('translation', $this->nodeSource->getTranslation());

            try {
                return $query->getSingleResult();
            } catch (\Doctrine\ORM\NoResultException $e) {
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * Get children nodes sources to lock with current translation.
     *
     * @param array|null                                      $criteria Additionnal criteria
     * @param array|null                                      $order Non default ordering
     * @param Symfony\Component\Security\Core\SecurityContext $securityContext
     *
     * @return ArrayCollection NodesSources collection
     */
    public function getChildren(
        array $criteria = null,
        array $order = null,
        SecurityContext $securityContext = null
    ) {

        $defaultCrit = array(
            'node.parent' => $this->nodeSource->getNode(),
            'node.status' => array('<=', Node::PUBLISHED),
            'translation' => $this->nodeSource->getTranslation()
        );

        if (null !== $order) {
            $defaultOrder = $order;
        } else {
            $defaultOrder = array (
                'node.position' => 'ASC'
            );
        }

        if (null !== $criteria) {
            $defaultCrit = array_merge($defaultCrit, $criteria);
        }

        if (null === $securityContext) {
            $securityContext = Kernel::getService('securityContext');
        }

        return Kernel::getService('em')
                            ->getRepository('RZ\Renzo\Core\Entities\NodesSources')
                            ->findBy(
                                $defaultCrit,
                                $defaultOrder,
                                null,
                                null,
                                $securityContext
                            );
    }

    /**
     * Get node tags with current source translation.
     *
     * @return ArrayCollection
     */
    public function getTags()
    {
        $tags = Kernel::getService('tagApi')->getBy(array(
            "nodes" => $this->nodeSource->getNode(),
            "translation" => $this->nodeSource->getTranslation()
        ));

        return $tags;
    }
}
