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
 * @file NodesSources.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectManagerAware;
use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use JMS\Serializer\Annotation as Serializer;

/**
 * NodesSources store Node content according to a translation and a NodeType.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\NodesSourcesRepository")
 * @ORM\Table(name="nodes_sources", uniqueConstraints={
 *     @ORM\UniqueConstraint(columns={"node_id", "translation_id"})
 * }, indexes={
 *     @ORM\Index(columns={"title"}),
 *     @ORM\Index(columns={"published_at"}),
 *     @ORM\Index(columns={"translation_id", "published_at"}, name="ns_translation_publishedat")
 * })
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\HasLifecycleCallbacks
 */
class NodesSources extends AbstractEntity implements ObjectManagerAware
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
     * @var Node
     * @ORM\ManyToOne(targetEntity="Node", inversedBy="nodeSources", fetch="EAGER", cascade={"persist"})
     * @ORM\JoinColumn(name="node_id", referencedColumnName="id", onDelete="CASCADE")
     * @Serializer\Groups({"nodes_sources", "log_sources"})
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
     * @var Translation
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
     * @var ArrayCollection
     * @Serializer\Groups({"nodes_sources"})
     */
    private $urlAliases;

    /**
     * @return Collection
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
     * @ORM\OneToMany(targetEntity="RZ\Roadiz\Core\Entities\NodesSourcesDocuments", mappedBy="nodeSource", orphanRemoval=true, cascade={"persist"}, fetch="LAZY")
     * @var ArrayCollection
     * @Serializer\Exclude
     */
    private $documentsByFields;

    /**
     * @return Collection
     */
    public function getDocumentsByFields(): Collection
    {
        return $this->documentsByFields;
    }

    /**
     * @param $fieldName
     * @return Document[]
     */
    public function getDocumentsByFieldsWithName($fieldName): array
    {
        $criteria = Criteria::create();
        $criteria->orderBy(['position' => 'ASC']);
        $relations = $this->getDocumentsByFields()
            ->matching($criteria)
            ->filter(function ($element) use ($fieldName) {
                if ($element instanceof NodesSourcesDocuments) {
                    return $element->getField()->getName() === $fieldName;
                }
                return false;
            });

        $documents = [];
        /** @var NodesSourcesDocuments $relation */
        foreach ($relations as $relation) {
            $documents[] = $relation->getDocument();
        }

        return $documents;
    }

    /**
     * @ORM\OneToMany(targetEntity="RZ\Roadiz\Core\Entities\Log", mappedBy="nodeSource")
     * @ORM\OrderBy({"datetime" = "DESC"})
     * @var ArrayCollection
     * @Serializer\Exclude
     */
    protected $logs;

    /**
     * Logs related to this node-source.
     *
     * @return Collection
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
     * @Serializer\Groups({"nodes_sources", "log_sources"})
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
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title): NodesSources
    {
        $this->title = trim($title);

        return $this;
    }

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", name="published_at", unique=false, nullable=true)
     * @Serializer\Groups({"nodes_sources"})
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
     * @param string $metaTitle
     *
     * @return $this
     */
    public function setMetaTitle($metaTitle): NodesSources
    {
        $this->metaTitle = trim($metaTitle);

        return $this;
    }
    /**
     * @ORM\Column(type="text", name="meta_keywords")
     * @Serializer\Groups({"nodes_sources"})
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
     * @param string $metaKeywords
     *
     * @return $this
     */
    public function setMetaKeywords($metaKeywords): NodesSources
    {
        $this->metaKeywords = trim($metaKeywords);

        return $this;
    }
    /**
     * @ORM\Column(type="text", name="meta_description")
     * @Serializer\Groups({"nodes_sources"})
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
     * @param String $metaDescription
     *
     * @return $this
     */
    public function setMetaDescription($metaDescription): NodesSources
    {
        $this->metaDescription = trim($metaDescription);

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
