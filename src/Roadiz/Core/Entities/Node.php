<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Loggable\Loggable;
use RZ\Roadiz\Attribute\Model\AttributableInterface;
use RZ\Roadiz\Attribute\Model\AttributableTrait;
use RZ\Roadiz\Core\AbstractEntities\AbstractDateTimedPositioned;
use RZ\Roadiz\Core\AbstractEntities\LeafInterface;
use RZ\Roadiz\Core\AbstractEntities\LeafTrait;
use RZ\Roadiz\Core\AbstractEntities\TranslationInterface;
use RZ\Roadiz\Utils\StringHandler;
use JMS\Serializer\Annotation as Serializer;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Node entities are the central feature of Roadiz,
 * it describes a document-like object which can be inherited
 * with *NodesSources* to create complex data structures.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\NodeRepository")
 * @ORM\Table(name="nodes", indexes={
 *     @ORM\Index(columns={"visible"}),
 *     @ORM\Index(columns={"status"}),
 *     @ORM\Index(columns={"locked"}),
 *     @ORM\Index(columns={"sterile"}),
 *     @ORM\Index(columns={"position"}),
 *     @ORM\Index(columns={"created_at"}),
 *     @ORM\Index(columns={"updated_at"}),
 *     @ORM\Index(columns={"hide_children"}),
 *     @ORM\Index(columns={"node_name", "status"}),
 *     @ORM\Index(columns={"visible", "status"}),
 *     @ORM\Index(columns={"visible", "status", "parent_node_id"}),
 *     @ORM\Index(columns={"visible", "parent_node_id"}),
 *     @ORM\Index(columns={"home"})
 * })
 * @ORM\HasLifecycleCallbacks
 * @Gedmo\Loggable(logEntryClass="RZ\Roadiz\Core\Entities\UserLogEntry")
 */
class Node extends AbstractDateTimedPositioned implements LeafInterface, AttributableInterface, Loggable
{
    use LeafTrait;
    use AttributableTrait;

    const DRAFT = 10;
    const PENDING = 20;
    const PUBLISHED = 30;
    const ARCHIVED = 40;
    const DELETED = 50;

    /**
     * @var array
     * @Serializer\Exclude
     */
    public static $orderingFields = [
        'position' => 'position',
        'nodeName' => 'nodeName',
        'createdAt' => 'createdAt',
        'updatedAt' => 'updatedAt',
        'publishedAt' => 'ns.publishedAt',
    ];

    /**
     * @param int $status
     * @return string
     */
    public static function getStatusLabel($status): string
    {
        $nodeStatuses = [
            static::DRAFT => 'draft',
            static::PENDING => 'pending',
            static::PUBLISHED => 'published',
            static::ARCHIVED => 'archived',
            static::DELETED => 'deleted',
        ];

        if (isset($nodeStatuses[$status])) {
            return $nodeStatuses[$status];
        }

        throw new \InvalidArgumentException('Status does not exist.');
    }

    /**
     * @ORM\Column(type="string", name="node_name", unique=true)
     * @Serializer\Groups({"nodes_sources", "nodes_sources_base", "node", "log_sources"})
     * @Serializer\Accessor(getter="getNodeName", setter="setNodeName")
     */
    private $nodeName = '';

    /**
     * @return string
     */
    public function getNodeName(): string
    {
        return $this->nodeName;
    }

    /**
     * @param string $nodeName
     * @return $this
     */
    public function setNodeName($nodeName): Node
    {
        $this->nodeName = StringHandler::slugify($nodeName);
        return $this;
    }

    /**
     * @ORM\Column(type="boolean", name="dynamic_node_name", nullable=false, options={"default" = true})
     * @Gedmo\Versioned
     */
    private $dynamicNodeName = true;

    /**
     * Dynamic node name will be updated against default
     * translated nodeSource title at each save.
     *
     * Disable this parameter if you need to protect your nodeName
     * from title changes.
     *
     * @return bool
     */
    public function isDynamicNodeName(): bool
    {
        return $this->dynamicNodeName;
    }

    /**
     * @param bool $dynamicNodeName
     * @return $this
     */
    public function setDynamicNodeName($dynamicNodeName): Node
    {
        $this->dynamicNodeName = (bool) $dynamicNodeName;
        return $this;
    }

    /**
     * @ORM\Column(type="boolean", name="home", nullable=false, options={"default" = false})
     * @Serializer\Groups({"nodes_sources_base", "nodes_sources", "node"})
     * @Serializer\Exclude(if="!object.getNodeType().isReachable()")
     */
    private $home = false;

    /**
     * @return bool
     */
    public function isHome(): bool
    {
        return $this->home;
    }

    /**
     * @param bool $home
     * @return $this
     */
    public function setHome(bool $home): Node
    {
        $this->home = $home;
        return $this;
    }

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default" = true})
     * @Gedmo\Versioned
     * @Serializer\Groups({"nodes_sources_base", "nodes_sources", "node"})
     */
    private $visible = true;

    /**
     * @return bool
     */
    public function isVisible(): bool
    {
        return $this->visible;
    }

    /**
     * @param bool $visible
     * @return $this
     */
    public function setVisible(bool $visible): Node
    {
        $this->visible = $visible;
        return $this;
    }

    /**
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"node"})
     * @internal You should use node Workflow to perform change on status.
     */
    private $status = Node::DRAFT;

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param int $status
     * @return $this
     * @internal You should use node Workflow to perform change on status.
     */
    public function setStatus($status)
    {
        $this->status = (int) $status;

        return $this;
    }

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false, options={"default" = 0})
     * @Gedmo\Versioned
     * @Serializer\Exclude()
     */
    private $ttl = 0;

    /**
     * @return int
     */
    public function getTtl(): int
    {
        return $this->ttl;
    }

    /**
     * @param int $ttl
     *
     * @return Node
     */
    public function setTtl(int $ttl): Node
    {
        $this->ttl = $ttl;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPublished()
    {
        return ($this->status === Node::PUBLISHED);
    }

    /**
     * @return bool
     */
    public function isPending()
    {
        return ($this->status === Node::PENDING);
    }

    /**
     * @return bool
     */
    public function isDraft()
    {
        return ($this->status === Node::DRAFT);
    }

    /**
     * @return bool
     */
    public function isDeleted()
    {
        return ($this->status === Node::DELETED);
    }

    /**
     * @param bool $published
     *
     * @return $this
     * @deprecated You should use node Workflow to perform change on status.
     */
    public function setPublished($published)
    {
        $this->status = ($published) ? Node::PUBLISHED : Node::PENDING;

        return $this;
    }

    /**
     * @param bool $pending
     *
     * @return $this
     * @deprecated You should use node Workflow to perform change on status.
     */
    public function setPending($pending)
    {
        $this->status = ($pending) ? Node::PENDING : Node::DRAFT;

        return $this;
    }

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default" = false})
     * @Gedmo\Versioned
     * @Serializer\Groups({"node"})
     */
    private $locked = false;

    /**
     * @return bool
     */
    public function isLocked()
    {
        return $this->locked;
    }

    /**
     * @param bool $locked
     *
     * @return $this
     */
    public function setLocked($locked)
    {
        $this->locked = (boolean) $locked;

        return $this;
    }

    /**
     * @ORM\Column(type="decimal", precision=2, scale=1)
     * @Gedmo\Versioned
     * @Serializer\Groups({"node"})
     */
    private $priority = 0.8;

    /**
     * @return float
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param float $priority
     *
     * @return $this
     */
    public function setPriority($priority)
    {
        $this->priority = (float) $priority;

        return $this;
    }

    /**
     * @var bool
     * @ORM\Column(type="boolean", name="hide_children", nullable=false, options={"default" = false})
     * @Gedmo\Versioned
     * @Serializer\Groups({"node"})
     */
    private $hideChildren = false;

    /**
     * @return mixed
     */
    public function getHideChildren()
    {
        return $this->hideChildren;
    }

    /**
     * @param mixed $hideChildren
     * @return Node
     */
    public function setHideChildren($hideChildren)
    {
        $this->hideChildren = (boolean) $hideChildren;
        return $this;
    }


    /**
     * @return boolean
     */
    public function isHidingChildren()
    {
        return $this->hideChildren;
    }

    /**
     * @param boolean $hideChildren
     *
     * @return $this
     */
    public function setHidingChildren($hideChildren)
    {
        $this->hideChildren = (boolean) $hideChildren;

        return $this;
    }

    /**
     * @return bool
     */
    public function isArchived()
    {
        return ($this->status === Node::ARCHIVED);
    }

    /**
     * @param bool $archived
     *
     * @return $this
     * @deprecated You should use node Workflow to perform change on status.
     */
    public function setArchived($archived)
    {
        $this->status = ($archived) ? Node::ARCHIVED : Node::PUBLISHED;

        return $this;
    }

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default" = false})
     * @Gedmo\Versioned
     * @Serializer\Groups({"node"})
     */
    private $sterile = false;

    /**
     * @return bool
     */
    public function isSterile()
    {
        return $this->sterile;
    }

    /**
     * @param bool $sterile
     *
     * @return $this
     */
    public function setSterile($sterile)
    {
        $this->sterile = (boolean) $sterile;

        return $this;
    }

    /**
     * @var string
     * @ORM\Column(type="string", name="children_order")
     * @Gedmo\Versioned
     * @Serializer\Groups({"node"})
     */
    private $childrenOrder = 'position';

    /**
     * @return string
     */
    public function getChildrenOrder()
    {
        return $this->childrenOrder;
    }

    /**
     * @param string $childrenOrder
     *
     * @return $this
     */
    public function setChildrenOrder($childrenOrder)
    {
        $this->childrenOrder = $childrenOrder;

        return $this;
    }

    /**
     * @var string
     * @ORM\Column(type="string", name="children_order_direction", length=4)
     * @Gedmo\Versioned
     * @Serializer\Groups({"node"})
     */
    private $childrenOrderDirection = 'ASC';

    /**
     * @return string
     */
    public function getChildrenOrderDirection()
    {
        return $this->childrenOrderDirection;
    }

    /**
     * @param string $childrenOrderDirection
     *
     * @return $this
     */
    public function setChildrenOrderDirection($childrenOrderDirection)
    {
        $this->childrenOrderDirection = $childrenOrderDirection;

        return $this;
    }

    /**
     * @ORM\ManyToOne(targetEntity="NodeType")
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @Serializer\Groups({"node"})
     * @var NodeType|null
     */
    private $nodeType = null;

    /**
     * @return NodeType|null
     */
    public function getNodeType()
    {
        return $this->nodeType;
    }

    /**
     * @param NodeType|null $nodeType
     *
     * @return $this
     */
    public function setNodeType(NodeType $nodeType = null)
    {
        $this->nodeType = $nodeType;

        return $this;
    }

    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\Node", inversedBy="children", fetch="EAGER")
     * @ORM\JoinColumn(name="parent_node_id", referencedColumnName="id", onDelete="CASCADE")
     * @var Node|null
     * @Serializer\Exclude
     */
    protected $parent = null;

    /**
     * @ORM\OneToMany(targetEntity="RZ\Roadiz\Core\Entities\Node", mappedBy="parent", orphanRemoval=true)
     * @ORM\OrderBy({"position" = "ASC"})
     * @var ArrayCollection<Node>
     * @Serializer\Groups({"node_children"})
     */
    protected $children;

    /**
     * @ORM\ManyToMany(targetEntity="Tag", inversedBy="nodes")
     * @ORM\JoinTable(name="nodes_tags")
     * @var Collection<Tag>
     * @Serializer\Groups({"nodes_sources", "nodes_sources_base", "node"})
     */
    private $tags;

    /**
     * @return Collection<Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    /**
     * @param Collection<Tag> $tags
     *
     * @return Node
     */
    public function setTags(Collection $tags): self
    {
        $this->tags = $tags;
        return $this;
    }

    /**
     * @param Tag $tag
     *
     * @return $this
     */
    public function removeTag(Tag $tag): self
    {
        if ($this->getTags()->contains($tag)) {
            $this->getTags()->removeElement($tag);
        }

        return $this;
    }

    /**
     * @param Tag $tag
     *
     * @return $this
     */
    public function addTag(Tag $tag): self
    {
        if (!$this->getTags()->contains($tag)) {
            $this->getTags()->add($tag);
        }

        return $this;
    }

    /**
     * @ORM\OneToMany(targetEntity="NodesCustomForms", mappedBy="node", fetch="EXTRA_LAZY")
     * @var Collection<NodesCustomForms>
     * @Serializer\Exclude()
     */
    private $customForms;

    /**
     * @return Collection<NodesCustomForms>
     */
    public function getCustomForms()
    {
        return $this->customForms;
    }

    /**
     * @param Collection<NodesCustomForms> $customForms
     * @return Node
     */
    public function setCustomForms(Collection $customForms): self
    {
        $this->customForms = $customForms;
        return $this;
    }

    /**
     * Used by generated nodes-sources.
     *
     * @param NodesCustomForms $nodesCustomForms
     * @return Node
     */
    public function addCustomForm(NodesCustomForms $nodesCustomForms): self
    {
        if (!$this->customForms->contains($nodesCustomForms)) {
            $this->customForms->add($nodesCustomForms);
        }
        return $this;
    }

    /**
     * @ORM\ManyToMany(targetEntity="NodeType")
     * @ORM\JoinTable(name="stack_types")
     * @var Collection<NodeType>
     * @Serializer\Groups({"node"})
     */
    private $stackTypes;

    /**
     * @return Collection<NodeType>
     */
    public function getStackTypes()
    {
        return $this->stackTypes;
    }

    /**
     * @param NodeType $stackType
     *
     * @return $this
     */
    public function removeStackType(NodeType $stackType)
    {
        if ($this->getStackTypes()->contains($stackType)) {
            $this->getStackTypes()->removeElement($stackType);
        }

        return $this;
    }

    /**
     * @param NodeType $stackType
     *
     * @return $this
     */
    public function addStackType(NodeType $stackType)
    {
        if (!$this->getStackTypes()->contains($stackType)) {
            $this->getStackTypes()->add($stackType);
        }

        return $this;
    }

    /**
     * @ORM\OneToMany(targetEntity="NodesSources", mappedBy="node", orphanRemoval=true, fetch="EXTRA_LAZY")
     * @Serializer\Groups({"node"})
     * @var Collection<NodesSources>
     */
    private $nodeSources;

    /**
     * @return Collection<NodesSources>
     */
    public function getNodeSources()
    {
        return $this->nodeSources;
    }

    /**
     * Get node-sources using a given translation.
     *
     * @param TranslationInterface $translation
     * @return Collection
     */
    public function getNodeSourcesByTranslation(TranslationInterface $translation)
    {
        return $this->nodeSources->filter(function (NodesSources $nodeSource) use ($translation) {
            return $nodeSource->getTranslation()->getLocale() === $translation->getLocale();
        });
    }

    /**
     * @param NodesSources $ns
     *
     * @return $this
     */
    public function removeNodeSources(NodesSources $ns)
    {
        if ($this->getNodeSources()->contains($ns)) {
            $this->getNodeSources()->removeElement($ns);
        }

        return $this;
    }

    /**
     * @param NodesSources $ns
     *
     * @return $this
     */
    public function addNodeSources(NodesSources $ns)
    {
        if (!$this->getNodeSources()->contains($ns)) {
            $this->getNodeSources()->add($ns);
        }

        return $this;
    }

    /**
     * @ORM\OneToMany(targetEntity="NodesToNodes", mappedBy="nodeA")
     * @ORM\OrderBy({"position" = "ASC"})
     * @var Collection<NodesToNodes>
     * @Serializer\Exclude()
     */
    protected $bNodes;

    /**
     * Return nodes related to this (B nodes).
     *
     * @return Collection<NodesToNodes>
     */
    public function getBNodes()
    {
        return $this->bNodes;
    }

    /**
     * @param NodeTypeField $field
     *
     * @return ArrayCollection|Collection
     */
    public function getBNodesByField(NodeTypeField $field)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('field', $field));
        $criteria->orderBy(['position' => 'ASC']);
        return $this->getBNodes()->matching($criteria);
    }

    /**
     * @ORM\OneToMany(targetEntity="NodesToNodes", mappedBy="nodeB")
     * @var Collection<NodesToNodes>
     * @Serializer\Exclude()
     */
    protected $aNodes;

    /**
     * Return nodes which own a relation with this (A nodes).
     *
     * @return Collection<NodesToNodes>
     */
    public function getANodes()
    {
        return $this->aNodes;
    }

    /**
     * @var Collection<AttributeValue>
     * @ORM\OneToMany(targetEntity="RZ\Roadiz\Core\Entities\AttributeValue", mappedBy="node", orphanRemoval=true)
     * @ORM\OrderBy({"position" = "ASC"})
     * @Serializer\Groups({"nodes_sources", "node"})
     */
    protected $attributeValues;

    /**
     * Create a new empty Node according to given node-type.
     *
     * @param NodeType|null $nodeType
     */
    public function __construct(NodeType $nodeType = null)
    {
        $this->tags = new ArrayCollection();
        $this->children = new ArrayCollection();
        $this->nodeSources = new ArrayCollection();
        $this->stackTypes = new ArrayCollection();
        $this->customForms = new ArrayCollection();
        $this->aNodes = new ArrayCollection();
        $this->bNodes = new ArrayCollection();
        $this->attributeValues = new ArrayCollection();

        $this->setNodeType($nodeType);
        $this->initAbstractDateTimed();
    }

    /**
     * @return string
     */
    public function getOneLineSummary()
    {
        return $this->getId() . " — " . $this->getNodeName() . " — " . $this->getNodeType()->getName() .
        " — Visible : " . ($this->isVisible() ? 'true' : 'false') . PHP_EOL;
    }

    /**
     * @return string
     */
    public function getOneLineSourceSummary()
    {
        $text = "Source " . $this->getNodeSources()->first()->getId() . PHP_EOL;

        foreach ($this->getNodeType()->getFields() as $field) {
            $getterName = $field->getGetterName();
            $text .= '[' . $field->getLabel() . ']: ' . $this->getNodeSources()->first()->$getterName() . PHP_EOL;
        }

        return $text;
    }

    /**
     * After clone method.
     *
     * Clone current node and ist relations.
     */
    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->home = false;
            $children = $this->getChildren();
            if ($children !== null) {
                $this->children = new ArrayCollection();
                foreach ($children as $child) {
                    $cloneChild = clone $child;
                    $this->addChild($cloneChild);
                }
            }
            $nodeSources = $this->getNodeSources();
            if ($nodeSources !== null) {
                $this->nodeSources = new ArrayCollection();
                /** @var NodesSources $nodeSource */
                foreach ($nodeSources as $nodeSource) {
                    $cloneNodeSource = clone $nodeSource;
                    $cloneNodeSource->setNode($this);
                }
            }
            $attributeValues = $this->getAttributeValues();
            if ($attributeValues !== null) {
                $this->attributeValues = new ArrayCollection();
                /** @var AttributeValue $attributeValue */
                foreach ($attributeValues as $attributeValue) {
                    $cloneAttributeValue = clone $attributeValue;
                    $cloneAttributeValue->setNode($this);
                    $this->addAttributeValue($cloneAttributeValue);
                }
            }
            // Get a random string after node-name.
            // This is for safety reasons
            // NodeDuplicator service will override it
            $namePrefix = $this->getNodeSources()->first()->getTitle() != "" ?
                $this->getNodeSources()->first()->getTitle() :
                $this->nodeName;
            $this->setNodeName($namePrefix . "-" . uniqid());
            $this->setCreatedAt(new \DateTime());
            $this->setUpdatedAt(new \DateTime());
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return '[Node]' . $this->getId() . " — " . $this->getNodeName() . " <" . $this->getNodeType()->getName() . '>';
    }
}
