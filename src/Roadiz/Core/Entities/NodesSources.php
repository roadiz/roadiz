<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectManagerAware;
use Gedmo\Loggable\Loggable;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use JMS\Serializer\Annotation as Serializer;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * NodesSources store Node content according to a translation and a NodeType.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\NodesSourcesRepository")
 * @ORM\Table(name="nodes_sources", uniqueConstraints={
 *     @ORM\UniqueConstraint(columns={"node_id", "translation_id"})
 * }, indexes={
 *     @ORM\Index(columns={"discr"}),
 *     @ORM\Index(columns={"discr", "translation_id"}),
 *     @ORM\Index(columns={"discr", "translation_id", "published_at"}, name="ns_disc_translation_publishedat"),
 *     @ORM\Index(columns={"title"}),
 *     @ORM\Index(columns={"published_at"}),
 *     @ORM\Index(columns={"node_id", "translation_id", "published_at"}),
 *     @ORM\Index(columns={"title", "published_at"}),
 *     @ORM\Index(columns={"title", "translation_id", "published_at"}),
 *     @ORM\Index(columns={"translation_id", "published_at"}, name="ns_translation_publishedat")
 * })
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\HasLifecycleCallbacks
 * @Gedmo\Loggable(logEntryClass="RZ\Roadiz\Core\Entities\UserLogEntry")
 */
class NodesSources extends AbstractEntity implements ObjectManagerAware, Loggable
{
    /**
     * @var ObjectManager
     * @Serializer\Exclude
     */
    protected $objectManager;

    /**
     * @inheritDoc
     * @Serializer\Exclude
     */
    public function injectObjectManager(ObjectManager $objectManager, ClassMetadata $classMetadata)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @var Node|null
     * @ORM\ManyToOne(targetEntity="Node", inversedBy="nodeSources", fetch="EAGER", cascade={"persist"})
     * @ORM\JoinColumn(name="node_id", referencedColumnName="id", onDelete="CASCADE")
     * @Serializer\Groups({"nodes_sources", "nodes_sources_base", "log_sources"})
     */
    private $node;

    /**
     * @return Node|null
     */
    public function getNode(): ?Node
    {
        return $this->node;
    }

    /**
     * @param Node|null $node
     *
     * @return $this
     */
    public function setNode(Node $node = null): NodesSources
    {
        $this->node = $node;
        if (null !== $node) {
            $node->addNodeSources($this);
        }

        return $this;
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        if (null !== $this->getNode()) {
            $this->getNode()->setUpdatedAt(new \DateTime("now"));
        }
    }

    /**
     * @var Translation|null
     * @ORM\ManyToOne(targetEntity="Translation", inversedBy="nodeSources")
     * @ORM\JoinColumn(name="translation_id", referencedColumnName="id", onDelete="CASCADE")
     * @Serializer\Groups({"nodes_sources", "log_sources"})
     */
    private $translation;

    /**
     * @return Translation
     */
    public function getTranslation(): Translation
    {
        return $this->translation;
    }
    /**
     * @param Translation $translation
     *
     * @return $this
     */
    public function setTranslation(Translation $translation): NodesSources
    {
        $this->translation = $translation;
        return $this;
    }

    /**
     * @ORM\OneToMany(targetEntity="RZ\Roadiz\Core\Entities\UrlAlias", mappedBy="nodeSource", cascade={"remove"})
     * @var Collection<UrlAlias>
     * @Serializer\Groups({"nodes_sources"})
     */
    private $urlAliases;

    /**
     * @return Collection<UrlAlias>
     */
    public function getUrlAliases(): Collection
    {
        return $this->urlAliases;
    }

    /**
     * @param UrlAlias $urlAlias
     * @return $this
     */
    public function addUrlAlias(UrlAlias $urlAlias): NodesSources
    {
        if (!$this->urlAliases->contains($urlAlias)) {
            $this->urlAliases->add($urlAlias);
            $urlAlias->setNodeSource($this);
        }

        return $this;
    }

    /**
     * @ORM\OneToMany(
     *     targetEntity="RZ\Roadiz\Core\Entities\NodesSourcesDocuments",
     *     mappedBy="nodeSource",
     *     orphanRemoval=true,
     *     cascade={"persist"},
     *     fetch="LAZY"
     * )
     * @var Collection<NodesSourcesDocuments>
     * @Serializer\Exclude
     */
    private $documentsByFields;

    /**
     * @return Collection<NodesSourcesDocuments>
     */
    public function getDocumentsByFields(): Collection
    {
        return $this->documentsByFields;
    }

    /**
     * @param ArrayCollection $documentsByFields
     *
     * @return NodesSources
     */
    public function setDocumentsByFields(ArrayCollection $documentsByFields): NodesSources
    {
        $this->documentsByFields = $documentsByFields;
        return $this;
    }

    /**
     * Used by any NSClass to add directly new documents to source.
     *
     * @param NodesSourcesDocuments $nodesSourcesDocuments
     *
     * @return $this
     */
    public function addDocumentsByFields(NodesSourcesDocuments $nodesSourcesDocuments): NodesSources
    {
        if (!$this->getDocumentsByFields()->contains($nodesSourcesDocuments)) {
            $this->getDocumentsByFields()->add($nodesSourcesDocuments);
        }
        return $this;
    }

    /**
     * @param NodeTypeField $field
     *
     * @return Document[]
     */
    public function getDocumentsByFieldsWithField(NodeTypeField $field): array
    {
        $criteria = Criteria::create();
        $criteria->orderBy(['position' => 'ASC']);
        return $this->getDocumentsByFields()
            ->matching($criteria)
            ->filter(function ($element) use ($field) {
                if ($element instanceof NodesSourcesDocuments) {
                    return $element->getField() === $field;
                }
                return false;
            })
            ->map(function (NodesSourcesDocuments $nodesSourcesDocuments) {
                return $nodesSourcesDocuments->getDocument();
            })
            ->toArray()
        ;
    }

    /**
     * @param string $fieldName
     * @return Document[]
     */
    public function getDocumentsByFieldsWithName($fieldName): array
    {
        $criteria = Criteria::create();
        $criteria->orderBy(['position' => 'ASC']);
        return $this->getDocumentsByFields()
            ->matching($criteria)
            ->filter(function ($element) use ($fieldName) {
                if ($element instanceof NodesSourcesDocuments) {
                    return $element->getField()->getName() === $fieldName;
                }
                return false;
            })
            ->map(function (NodesSourcesDocuments $nodesSourcesDocuments) {
                return $nodesSourcesDocuments->getDocument();
            })
            ->toArray()
        ;
    }

    /**
     * @ORM\OneToMany(targetEntity="RZ\Roadiz\Core\Entities\Log", mappedBy="nodeSource")
     * @ORM\OrderBy({"datetime" = "DESC"})
     * @var Collection<Log>
     * @Serializer\Exclude
     */
    protected $logs;

    /**
     * Logs related to this node-source.
     *
     * @return Collection<Log>
     */
    public function getLogs(): Collection
    {
        return $this->logs;
    }

    /**
     * @param Collection $logs
     * @return $this
     */
    public function setLogs(Collection $logs): NodesSources
    {
        $this->logs = $logs;

        return $this;
    }

    /**
     * @ORM\Column(type="string", name="title", unique=false, nullable=true)
     * @Serializer\Groups({"nodes_sources", "nodes_sources_base", "log_sources"})
     * @Gedmo\Versioned
     */
    protected $title = '';

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string|null $title
     *
     * @return $this
     */
    public function setTitle($title): NodesSources
    {
        $this->title = null !== $title ? trim($title) : '';

        return $this;
    }

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime", name="published_at", unique=false, nullable=true)
     * @Serializer\Groups({"nodes_sources", "nodes_sources_base"})
     * @Gedmo\Versioned
     */
    protected $publishedAt;

    /**
     * @return \DateTime|null
     */
    public function getPublishedAt(): ?\DateTime
    {
        return $this->publishedAt;
    }

    /**
     * @param \DateTime|null $publishedAt
     * @return NodesSources
     */
    public function setPublishedAt(\DateTime $publishedAt = null): NodesSources
    {
        $this->publishedAt = $publishedAt;
        return $this;
    }

    /**
     * @ORM\Column(type="string", name="meta_title", unique=false)
     * @Serializer\Groups({"nodes_sources"})
     * @Gedmo\Versioned
     */
    protected $metaTitle = '';

    /**
     * @return string
     */
    public function getMetaTitle(): string
    {
        return $this->metaTitle;
    }

    /**
     * @param string|null $metaTitle
     *
     * @return $this
     */
    public function setMetaTitle($metaTitle): NodesSources
    {
        $this->metaTitle = null !== $metaTitle ? trim($metaTitle) : '';

        return $this;
    }
    /**
     * @ORM\Column(type="text", name="meta_keywords")
     * @Serializer\Groups({"nodes_sources"})
     * @Gedmo\Versioned
     */
    protected $metaKeywords = '';

    /**
     * @return string
     */
    public function getMetaKeywords(): string
    {
        return $this->metaKeywords;
    }

    /**
     * @param string|null $metaKeywords
     *
     * @return $this
     */
    public function setMetaKeywords($metaKeywords): NodesSources
    {
        $this->metaKeywords = null !== $metaKeywords ? trim($metaKeywords) : '';

        return $this;
    }
    /**
     * @ORM\Column(type="text", name="meta_description")
     * @Serializer\Groups({"nodes_sources"})
     * @Gedmo\Versioned
     */
    protected $metaDescription = '';

    /**
     * @return string
     */
    public function getMetaDescription(): string
    {
        return $this->metaDescription;
    }

    /**
     * @param string|null $metaDescription
     *
     * @return $this
     */
    public function setMetaDescription($metaDescription): NodesSources
    {
        $this->metaDescription = null !== $metaDescription ? trim($metaDescription) : '';

        return $this;
    }

    /**
     * Create a new NodeSource with its Node and Translation.
     *
     * @param Node        $node
     * @param Translation $translation
     */
    public function __construct(Node $node, Translation $translation)
    {
        $this->setNode($node);
        $this->translation = $translation;
        $this->urlAliases = new ArrayCollection();
        $this->documentsByFields = new ArrayCollection();
        $this->logs = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        $urlAlias = $this->getUrlAliases()->first();
        if (false !== $urlAlias && $urlAlias->getAlias() !== '') {
            return $urlAlias->getAlias();
        }

        return $this->getNode()->getNodeName();
    }

    /**
     * Get parent node’ source based on the same translation.
     *
     * @return NodesSources|null
     * @Serializer\Exclude
     */
    public function getParent(): ?NodesSources
    {
        if (null !== $this->getNode()->getParent()) {
            /** @var NodesSources|false $nodeSources */
            $nodeSources = $this->getNode()->getParent()->getNodeSourcesByTranslation($this->translation)->first();
            return $nodeSources ?: null;
        } else {
            return null;
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return '#' . $this->getId() .
        ' <' . $this->getTitle() . '>[' . $this->getTranslation()->getLocale() .
        '], type="' . $this->getNode()->getNodeType()->getName() . '"';
    }

    /**
     * After clone method.
     *
     * Be careful not to persist nor flush current entity after
     * calling clone as it empties its relations.
     */
    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $documentsByFields = $this->getDocumentsByFields();
            if ($documentsByFields !== null) {
                $this->documentsByFields = new ArrayCollection();
                foreach ($documentsByFields as $documentsByField) {
                    $cloneDocumentsByField = clone $documentsByField;
                    $this->documentsByFields->add($cloneDocumentsByField);
                    $cloneDocumentsByField->setNodeSource($this);
                }
            }
            // Clear url-aliases before cloning.
            if ($this->urlAliases !== null) {
                $this->urlAliases->clear();
            }
            // Clear logs before cloning.
            if ($this->logs !== null) {
                $this->logs->clear();
            }
        }
    }
}
