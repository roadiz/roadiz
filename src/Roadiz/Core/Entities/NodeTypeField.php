<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Entities;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use RZ\Roadiz\Contracts\NodeType\NodeTypeFieldInterface;
use RZ\Roadiz\Core\AbstractEntities\AbstractField;

/**
 * NodeTypeField entities are used to create NodeTypes with
 * custom data structure.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\NodeTypeFieldRepository")
 * @ORM\Table(name="node_type_fields", indexes={
 *         @ORM\Index(columns={"visible"}),
 *         @ORM\Index(columns={"indexed"}),
 *         @ORM\Index(columns={"position"}),
 *         @ORM\Index(columns={"group_name"}),
 *         @ORM\Index(columns={"group_name_canonical"}),
 *         @ORM\Index(columns={"type"}),
 *         @ORM\Index(columns={"universal"})
 *     },
 *     uniqueConstraints={@ORM\UniqueConstraint(columns={"name", "node_type_id"})}
 * )
 * @ORM\HasLifecycleCallbacks
 */
class NodeTypeField extends AbstractField implements NodeTypeFieldInterface
{
    /**
     * If current field data should be the same over translations or not.
     *
     * @var bool
     * @ORM\Column(name="universal", type="boolean", nullable=false, options={"default" = false})
     * @Serializer\Groups({"node_type"})
     * @Serializer\Type("bool")
     */
    private $universal = false;

    /**
     * Exclude current field from full-text search engines.
     *
     * @var bool
     * @ORM\Column(name="exclude_from_search", type="boolean", nullable=false, options={"default" = false})
     * @Serializer\Groups({"node_type"})
     * @Serializer\Type("bool")
     */
    private $excludeFromSearch = false;

    /**
     * @var NodeType|null
     * @ORM\ManyToOne(targetEntity="NodeType", inversedBy="fields")
     * @ORM\JoinColumn(name="node_type_id", onDelete="CASCADE")
     * @Serializer\Exclude()
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
     * @param NodeType $nodeType
     *
     * @return $this
     */
    public function setNodeType($nodeType)
    {
        $this->nodeType = $nodeType;

        return $this;
    }

    /**
     * @return string
     * @Serializer\VirtualProperty()
     * @Serializer\Type("string")
     * @Serializer\SerializedName("nodeTypeName")
     * @Serializer\Groups({"node_type"})
     */
    public function getNodeTypeName(): string
    {
        return $this->getNodeType()->getName();
    }

    /**
     * @var int|null
     * @ORM\Column(name="min_length", type="integer", nullable=true)
     * @Serializer\Groups({"node_type"})
     * @Serializer\Type("int")
     */
    private $minLength = null;

    /**
     * @return int|null
     */
    public function getMinLength(): ?int
    {
        return $this->minLength;
    }

    /**
     * @param int $minLength
     *
     * @return $this
     */
    public function setMinLength($minLength)
    {
        $this->minLength = $minLength;

        return $this;
    }

    /**
     * @var int|null
     * @ORM\Column(name="max_length", type="integer", nullable=true)
     * @Serializer\Groups({"node_type"})
     * @Serializer\Type("int")
     */
    private $maxLength = null;

    /**
     * @return int|null
     */
    public function getMaxLength(): ?int
    {
        return $this->maxLength;
    }

    /**
     * @param int $maxLength
     *
     * @return $this
     */
    public function setMaxLength($maxLength)
    {
        $this->maxLength = $maxLength;

        return $this;
    }

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default" = false})
     * @Serializer\Groups({"node_type"})
     * @Serializer\Type("bool")
     */
    private $indexed = false;

    /**
     * @return boolean $isIndexed
     */
    public function isIndexed(): bool
    {
        return $this->indexed;
    }

    /**
     * @param boolean $indexed
     *
     * @return $this
     */
    public function setIndexed($indexed)
    {
        $this->indexed = $indexed;

        return $this;
    }

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default" = true})
     * @Serializer\Groups({"node_type"})
     * @Serializer\Type("bool")
     */
    private $visible = true;

    /**
     * @return boolean $isVisible
     */
    public function isVisible(): bool
    {
        return $this->visible;
    }

    /**
     * @param boolean $visible
     *
     * @return $this
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Tell if current field can be searched and indexed in a Search engine server.
     *
     * @return bool
     */
    public function isSearchable(): bool
    {
        return !$this->excludeFromSearch && (boolean) in_array($this->getType(), static::$searchableTypes);
    }

    /**
     * @return string
     */
    public function getOneLineSummary()
    {
        return $this->getId() . " — " . $this->getLabel() . ' ['.$this->getName().']' .
        ' - ' . $this->getTypeName() .
        ($this->isIndexed() ? ' - indexed' : '') .
        (!$this->isVisible() ? ' - hidden' : '') . PHP_EOL;
    }

    /**
     * @see Same as isUniversal
     * @return mixed
     */
    public function getUniversal()
    {
        return $this->universal;
    }

    /**
     * @return bool
     */
    public function isUniversal(): bool
    {
        return $this->universal;
    }

    /**
     * @param bool $universal
     * @return NodeTypeField
     */
    public function setUniversal($universal)
    {
        $this->universal = (bool) $universal;
        return $this;
    }

    /**
     * @return bool
     */
    public function getExcludeFromSearch()
    {
        return $this->excludeFromSearch;
    }

    /**
     * @return bool
     */
    public function isExcludeFromSearch()
    {
        return $this->getExcludeFromSearch();
    }

    /**
     * @return bool
     */
    public function isExcludedFromSearch()
    {
        return $this->getExcludeFromSearch();
    }

    /**
     * @param bool $excludeFromSearch
     *
     * @return NodeTypeField
     */
    public function setExcludeFromSearch(bool $excludeFromSearch)
    {
        $this->excludeFromSearch = $excludeFromSearch;

        return $this;
    }
}
