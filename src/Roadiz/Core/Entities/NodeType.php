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
 * @file NodeType.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use RZ\Roadiz\Utils\StringHandler;
use JMS\Serializer\Annotation as Serializer;

/**
 * NodeType describes each node structure family,
 * They are mandatory before creating any Node.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\NodeTypeRepository")
 * @ORM\Table(name="node_types", indexes={
 *     @ORM\Index(columns={"visible"}),
 *     @ORM\Index(columns={"publishable"}),
 *     @ORM\Index(columns={"newsletter_type"}),
 *     @ORM\Index(columns={"hiding_nodes"}),
 *     @ORM\Index(columns={"reachable"})
 * })
 */
class NodeType extends AbstractEntity
{
    /**
     * @ORM\Column(type="string", unique=true)
     * @Serializer\Groups({"node_type", "node", "nodes_sources"})
     */
    private $name;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name): NodeType
    {
        $this->name = StringHandler::classify($name);
        return $this;
    }

    /**
     * @var string
     * @ORM\Column(name="display_name", type="string")
     * @Serializer\Groups({"node_type", "node", "nodes_soutces"})
     */
    private $displayName;

    /**
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    /**
     * @param string $displayName
     *
     * @return $this
     */
    public function setDisplayName(string $displayName): NodeType
    {
        $this->displayName = $displayName;
        return $this;
    }

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     * @Serializer\Groups("node_type")
     */
    private $description;

    /**
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription(string $description = null)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default" = true})
     * @Serializer\Groups("node_type")
     */
    private $visible = true;

    /**
     * @return boolean
     */
    public function isVisible(): bool
    {
        return $this->visible;
    }

    /**
     * @param boolean $visible
     * @return $this
     */
    public function setVisible(bool $visible): NodeType
    {
        $this->visible = $visible;
        return $this;
    }

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default" = false})
     * @Serializer\Groups("node_type")
     */
    private $publishable = false;

    /**
     * @return bool
     */
    public function isPublishable(): bool
    {
        return $this->publishable;
    }

    /**
     * @param bool $publishable
     * @return NodeType
     */
    public function setPublishable(bool $publishable): NodeType
    {
        $this->publishable = $publishable;
        return $this;
    }

    /**
     * Define if this node-type produces nodes that will be
     * viewable from a Controller.
     *
     * Typically if a node has an URL.
     *
     * @var bool
     * @ORM\Column(name="reachable", type="boolean", nullable=false, options={"default" = true})
     * @Serializer\Groups("node_type")
     */
    private $reachable = true;

    /**
     * @return bool
     */
    public function getReachable(): bool
    {
        return $this->reachable;
    }

    /**
     * @return bool
     */
    public function isReachable(): bool
    {
        return $this->getReachable();
    }

    /**
     * @param bool $reachable
     * @return NodeType
     */
    public function setReachable(bool $reachable): NodeType
    {
        $this->reachable = $reachable;
        return $this;
    }

    /**
     * @var bool
     * @ORM\Column(name="newsletter_type", type="boolean", nullable=false, options={"default" = false})
     * @Serializer\Groups("node_type")
     */
    private $newsletterType = false;

    /**
     * @return boolean
     */
    public function isNewsletterType(): bool
    {
        return $this->newsletterType;
    }
    /**
     * @param boolean $newsletterType
     *
     * @return $this
     */
    public function setNewsletterType(bool $newsletterType): NodeType
    {
        $this->newsletterType = $newsletterType;

        return $this;
    }
    /**
     * @var bool
     * @ORM\Column(name="hiding_nodes",type="boolean", nullable=false, options={"default" = false})
     * @Serializer\Groups("node_type")
     */
    private $hidingNodes = false;
    /**
     * @return boolean
     */
    public function isHidingNodes(): bool
    {
        return $this->hidingNodes;
    }
    /**
     * @param boolean $hidingNodes
     *
     * @return $this
     */
    public function setHidingNodes(bool $hidingNodes): NodeType
    {
        $this->hidingNodes = $hidingNodes;
        return $this;
    }
    /**
     * @ORM\Column(type="string", name="color", unique=false, nullable=true)
     * @Serializer\Groups({"node_type", "color"})
     */
    protected $color = '#000000';

    /**
     * Gets the value of color.
     *
     * @return string
     */
    public function getColor(): string
    {
        return $this->color;
    }

    /**
     * Sets the value of color.
     *
     * @param string $color
     *
     * @return $this
     */
    public function setColor(string $color): NodeType
    {
        $this->color = $color;

        return $this;
    }

    /**
     * @ORM\OneToMany(targetEntity="NodeTypeField", mappedBy="nodeType", cascade={"ALL"})
     * @ORM\OrderBy({"position" = "ASC"})
     * @Serializer\Groups("node_type")
     * @var ArrayCollection
     */
    private $fields;

    /**
     * @return Selectable
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @var int
     * @ORM\Column(type="integer", name="default_ttl", nullable=false, options={"default" = 0})
     * @Serializer\Exclude()
     */
    private $defaultTtl = 0;

    /**
     * @return int
     */
    public function getDefaultTtl(): int
    {
        return $this->defaultTtl;
    }

    /**
     * @param int $defaultTtl
     *
     * @return NodeType
     */
    public function setDefaultTtl(int $defaultTtl): NodeType
    {
        $this->defaultTtl = $defaultTtl;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return NodeTypeField|null
     */
    public function getFieldByName(string $name): ?NodeTypeField
    {
        $fieldCriteria = Criteria::create();
        $fieldCriteria->andWhere(Criteria::expr()->eq('name', $name));
        $fieldCriteria->setMaxResults(1);
        $field = $this->getFields()->matching($fieldCriteria)->first();
        return $field ?: null;
    }
    /**
     * Get every node-type fields names in
     * a simple array.
     *
     * @return array
     */
    public function getFieldsNames(): array
    {
        return array_map(function (NodeTypeField $field) {
            return $field->getName();
        }, $this->getFields()->toArray());
    }

    /**
     * @param NodeTypeField $field
     *
     * @return NodeType
     */
    public function addField(NodeTypeField $field): NodeType
    {
        if (!$this->getFields()->contains($field)) {
            $this->getFields()->add($field);
        }

        return $this;
    }

    /**
     * @param NodeTypeField $field
     *
     * @return NodeType
     */
    public function removeField(NodeTypeField $field): NodeType
    {
        if ($this->getFields()->contains($field)) {
            $this->getFields()->removeElement($field);
        }

        return $this;
    }

    /**
     * Create a new NodeType.
     */
    public function __construct()
    {
        $this->fields = new ArrayCollection();
        $this->name = 'Untitled';
        $this->displayName = 'Untitled node-type';
    }

    /**
     * Get node-source entity class name without its namespace.
     *
     * @return string
     */
    public function getSourceEntityClassName(): string
    {
        return 'NS' . ucwords($this->getName());
    }

    /**
     * @return string
     */
    public function getSourceEntityFullQualifiedClassName(): string
    {
        return static::getGeneratedEntitiesNamespace() . '\\' . $this->getSourceEntityClassName();
    }

    /**
     * Get node-source entity database table name.
     *
     * @return string
     */
    public function getSourceEntityTableName(): string
    {
        return 'ns_' . strtolower($this->getName());
    }

    /**
     * @return string
     */
    public static function getGeneratedEntitiesNamespace(): string
    {
        return 'GeneratedNodeSources';
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return '[#' . $this->getId() . '] ' . $this->getName() . ' ('.$this->getDisplayName().')';
    }

    /**
     * Get every searchable node-type fields as a Doctrine ArrayCollection.
     *
     * @return ArrayCollection
     */
    public function getSearchableFields(): ArrayCollection
    {
        $searchable = new ArrayCollection();
        /** @var NodeTypeField $field */
        foreach ($this->getFields() as $field) {
            if ($field->isSearchable()) {
                $searchable->add($field);
            }
        }
        return $searchable;
    }
}
