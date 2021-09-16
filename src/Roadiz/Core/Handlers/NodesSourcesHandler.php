<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Handlers;

use Doctrine\Persistence\ObjectManager;
use RZ\Roadiz\CMS\Utils\TagApi;
use RZ\Roadiz\Core\Bags\Settings;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodesSourcesDocuments;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Repositories\NodesSourcesRepository;

/**
 * Handle operations with node-sources entities.
 */
class NodesSourcesHandler extends AbstractHandler
{
    protected ?NodesSources $nodeSource = null;
    /**
     * @var array<NodesSources>|null
     */
    protected ?array $parentsNodeSources = null;
    protected Settings $settingsBag;
    protected TagApi $tagApi;

    /**
     * Create a new node-source handler with node-source to handle.
     *
     * @param ObjectManager $objectManager
     * @param Settings $settingsBag
     * @param TagApi $tagApi
     */
    public function __construct(ObjectManager $objectManager, Settings $settingsBag, TagApi $tagApi)
    {
        parent::__construct($objectManager);

        $this->settingsBag = $settingsBag;
        $this->tagApi = $tagApi;
    }

    /**
     * @return NodesSourcesRepository
     */
    protected function getRepository(): NodesSourcesRepository
    {
        return $this->objectManager->getRepository(NodesSources::class);
    }

    /**
     * @return NodesSources
     */
    public function getNodeSource(): NodesSources
    {
        if (null === $this->nodeSource) {
            throw new \BadMethodCallException('NodesSources is null');
        }
        return $this->nodeSource;
    }

    /**
     * @param NodesSources $nodeSource
     * @return NodesSourcesHandler
     */
    public function setNodeSource(NodesSources $nodeSource)
    {
        $this->nodeSource = $nodeSource;
        return $this;
    }

    /**
     * Remove every node-source documents associations for a given field.
     *
     * @param NodeTypeField $field
     * @param bool $flush
     * @return $this
     */
    public function cleanDocumentsFromField(NodeTypeField $field, bool $flush = true)
    {
        $this->nodeSource->clearDocumentsByFields($field);

        if (true === $flush) {
            $this->objectManager->flush();
        }

        return $this;
    }

    /**
     * Add a document to current node-source for a given node-type field.
     *
     * @param Document $document
     * @param NodeTypeField $field
     * @param bool $flush
     * @param null|float $position
     * @return $this
     */
    public function addDocumentForField(
        Document $document,
        NodeTypeField $field,
        bool $flush = true,
        ?float $position = null
    ) {
        $nsDoc = new NodesSourcesDocuments($this->nodeSource, $document, $field);

        if (!$this->nodeSource->hasNodesSourcesDocuments($nsDoc)) {
            if (null === $position) {
                $latestPosition = $this->objectManager
                    ->getRepository(NodesSourcesDocuments::class)
                    ->getLatestPosition($this->nodeSource, $field);

                $nsDoc->setPosition($latestPosition + 1);
            } else {
                $nsDoc->setPosition($position);
            }
            $this->nodeSource->addDocumentsByFields($nsDoc);
            $this->objectManager->persist($nsDoc);
            if (true === $flush) {
                $this->objectManager->flush();
            }
        }

        return $this;
    }

    /**
     * Get documents linked to current node-source for a given field name.
     *
     * @param string $fieldName Name of the node-type field
     * @return array<Document>
     */
    public function getDocumentsFromFieldName(string $fieldName): array
    {
        $field = $this->nodeSource->getNode()->getNodeType()->getFieldByName($fieldName);
        if (null !== $field) {
            return $this->objectManager
                ->getRepository(Document::class)
                ->findByNodeSourceAndField(
                    $this->nodeSource,
                    $field
                );
        }
        return [];
    }

    /**
     * Get a string describing uniquely the current nodeSource.
     *
     * Can be the urlAlias or the nodeName
     * @deprecated Use directly NodesSources::getIdentifier
     * @return string
     */
    public function getIdentifier(): string
    {
        $urlalias = $this->nodeSource->getUrlAliases()->first();
        if (is_object($urlalias)) {
            return $urlalias->getAlias();
        }

        return $this->nodeSource->getNode()->getNodeName();
    }

    /**
     * Get parent node-source to get the current translation.
     *
     * @deprecated Use directly NodesSources::getParent
     * @return NodesSources|null
     */
    public function getParent(): ?NodesSources
    {
        return $this->nodeSource->getParent();
    }

    /**
     * Get every nodeSources parents from direct parent to farest ancestor.
     *
     * @param array|null $criteria
     * @return array<NodesSources>
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getParents(
        array $criteria = null
    ): array {
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
                $currentParent = $this->getRepository()->findOneBy(
                    $criteria,
                    []
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
     * @param array|null $criteria Additional criteria
     * @param array|null $order Non default ordering
     *
     * @return array<object|NodesSources>
     */
    public function getChildren(
        array $criteria = null,
        array $order = null
    ): array {
        $defaultCrit = [
            'node.parent' => $this->nodeSource->getNode(),
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

        return $this->getRepository()->findBy(
            $defaultCrit,
            $defaultOrder
        );
    }

    /**
     * Get first node-source among current node-source children.
     *
     * @param array|null $criteria
     * @param array|null $order
     *
     * @return NodesSources|null
     */
    public function getFirstChild(
        array $criteria = null,
        array $order = null
    ): ?NodesSources {
        $defaultCrit = [
            'node.parent' => $this->nodeSource->getNode(),
            'translation' => $this->nodeSource->getTranslation()
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

        return $this->getRepository()->findOneBy(
            $defaultCrit,
            $defaultOrder
        );
    }
    /**
     * Get last node-source among current node-source children.
     *
     * @param  array|null $criteria
     * @param  array|null $order
     *
     * @return NodesSources|null
     */
    public function getLastChild(
        array $criteria = null,
        array $order = null
    ): ?NodesSources {
        $defaultCrit = [
            'node.parent' => $this->nodeSource->getNode(),
            'translation' => $this->nodeSource->getTranslation(),
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

        return $this->getRepository()->findOneBy(
            $defaultCrit,
            $defaultOrder
        );
    }

    /**
     * Get first node-source in the same parent as current node-source.
     *
     * @param  array|null $criteria
     * @param  array|null $order
     *
     * @return NodesSources|null
     */
    public function getFirstSibling(
        array $criteria = null,
        array $order = null
    ): ?NodesSources {
        if (null !== $this->nodeSource->getParent()) {
            $parentHandler = new NodesSourcesHandler($this->objectManager, $this->settingsBag, $this->tagApi);
            $parentHandler->setNodeSource($this->nodeSource->getParent());
            return $parentHandler->getFirstChild($criteria, $order);
        } else {
            $criteria['node.parent'] = null;
            return $this->getFirstChild($criteria, $order);
        }
    }

    /**
     * Get last node-source in the same parent as current node-source.
     *
     * @param array|null $criteria
     * @param array|null $order
     *
     * @return NodesSources|null
     */
    public function getLastSibling(
        array $criteria = null,
        array $order = null
    ): ?NodesSources {
        if (null !== $this->nodeSource->getParent()) {
            $parentHandler = new NodesSourcesHandler($this->objectManager, $this->settingsBag, $this->tagApi);
            $parentHandler->setNodeSource($this->nodeSource->getParent());
            return $parentHandler->getLastChild($criteria, $order);
        } else {
            $criteria['node.parent'] = null;
            return $this->getLastChild($criteria, $order);
        }
    }

    /**
     * Get previous node-source from hierarchy.
     *
     * @param array|null $criteria
     * @param array|null $order
     *
     * @return NodesSources|null
     */
    public function getPrevious(
        array $criteria = null,
        array $order = null
    ): ?NodesSources {
        if ($this->nodeSource->getNode()->getPosition() <= 1) {
            return null;
        }

        $defaultCriteria = [
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
            $defaultCriteria = array_merge($defaultCriteria, $criteria);
        }

        if (null === $order) {
            $order = [];
        }

        $order['node.position'] = 'DESC';

        return $this->getRepository()->findOneBy(
            $defaultCriteria,
            $order
        );
    }

    /**
     * Get next node-source from hierarchy.
     *
     * @param array|null $criteria
     * @param array|null $order
     *
     * @return NodesSources|null
     */
    public function getNext(
        array $criteria = null,
        array $order = null
    ): ?NodesSources {
        $defaultCrit = [
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

        return $this->getRepository()->findOneBy(
            $defaultCrit,
            $order
        );
    }

    /**
     * Get node tags with current source translation.
     *
     * @return array<Tag>
     */
    public function getTags()
    {
        return $this->tagApi->getBy([
            "nodes" => $this->nodeSource->getNode(),
            "translation" => $this->nodeSource->getTranslation(),
        ]);
    }

    /**
     * Get current node-source SEO data.
     *
     * This method returns a 3-fields array with:
     *
     * * title
     * * description
     * * keywords
     *
     * @return array<string>
     */
    public function getSEO(): array
    {
        return [
            'title' => ($this->nodeSource->getMetaTitle() != "") ?
            $this->nodeSource->getMetaTitle() :
            $this->nodeSource->getTitle() . ' – ' . $this->settingsBag->get('site_name'),
            'description' => ($this->nodeSource->getMetaDescription() != "") ?
            $this->nodeSource->getMetaDescription() :
            $this->nodeSource->getTitle() . ', ' . $this->settingsBag->get('seo_description'),
            'keywords' => $this->nodeSource->getMetaKeywords(),
        ];
    }

    /**
     * Get nodes linked to current node for a given fieldname.
     *
     * @param string $fieldName Name of the node-type field
     *
     * @return array<Node> Collection of nodes
     */
    public function getNodesFromFieldName(string $fieldName)
    {
        $field = $this->nodeSource->getNode()->getNodeType()->getFieldByName($fieldName);
        if (null !== $field) {
            return $this->objectManager
                ->getRepository(Node::class)
                ->findByNodeAndFieldAndTranslation(
                    $this->nodeSource->getNode(),
                    $field,
                    $this->nodeSource->getTranslation()
                );
        }
        return [];
    }

    /**
     * Get nodes which own a reference to current node for a given fieldname.
     *
     * @param string $fieldName Name of the node-type field
     *
     * @return array<Node> Collection of nodes
     */
    public function getReverseNodesFromFieldName(string $fieldName)
    {
        $field = $this->nodeSource->getNode()->getNodeType()->getFieldByName($fieldName);
        if (null !== $field) {
            return $this->objectManager
                ->getRepository(Node::class)
                ->findByReverseNodeAndFieldAndTranslation(
                    $this->nodeSource->getNode(),
                    $field,
                    $this->nodeSource->getTranslation()
                );
        }
        return [];
    }
}
