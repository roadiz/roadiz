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
use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use RZ\Roadiz\Core\Handlers\NodeTypeHandler;
use RZ\Roadiz\Utils\StringHandler;

/**
 * NodeTypes describe each node structure family,
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
     */
    private $name;
    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = StringHandler::classify($name);

        return $this;
    }

    /**
     * @var string
     * @ORM\Column(name="display_name", type="string")
     */
    private $displayName;

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * @param string $displayName
     *
     * @return $this
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;

        return $this;
    }

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default" = true})
     */
    private $visible = true;

    /**
     * @return boolean
     */
    public function isVisible()
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
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default" = false})
     */
    private $publishable = false;

    /**
     * @return bool
     */
    public function isPublishable()
    {
        return $this->publishable;
    }

    /**
     * @param bool $publishable
     * @return NodeType
     */
    public function setPublishable($publishable)
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
     */
    private $reachable = true;

    /**
     * @return bool
     */
    public function getReachable()
    {
        return $this->reachable;
    }

    /**
     * @return bool
     */
    public function isReachable()
    {
        return $this->getReachable();
    }

    /**
     * @param bool $reachable
     * @return NodeType
     */
    public function setReachable($reachable)
    {
        $this->reachable = (boolean) $reachable;
        return $this;
    }

    /**
     * @var bool
     * @ORM\Column(name="newsletter_type", type="boolean", nullable=false, options={"default" = false})
     */
    private $newsletterType = false;
    /**
     * @return boolean
     */
    public function isNewsletterType()
    {
        return $this->newsletterType;
    }
    /**
     * @param boolean $newsletterType
     *
     * @return $this
     */
    public function setNewsletterType($newsletterType)
    {
        $this->newsletterType = $newsletterType;

        return $this;
    }
    /**
     * @var bool
     * @ORM\Column(name="hiding_nodes",type="boolean", nullable=false, options={"default" = false})
     */
    private $hidingNodes = false;
    /**
     * @return boolean
     */
    public function isHidingNodes()
    {
        return $this->hidingNodes;
    }
    /**
     * @param boolean $hidingNodes
     *
     * @return $this
     */
    public function setHidingNodes($hidingNodes)
    {
        $this->hidingNodes = $hidingNodes;

        return $this;
    }
    /**
     * @ORM\Column(type="string", name="color", unique=false, nullable=true)
     */
    protected $color = '#000000';

    /**
     * Gets the value of color.
     *
     * @return string
     */
    public function getColor()
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
    public function setColor($color)
    {
        $this->color = $color;

        return $this;
    }

    /**
     * @ORM\OneToMany(targetEntity="NodeTypeField", mappedBy="nodeType", cascade={"ALL"})
     * @ORM\OrderBy({"position" = "ASC"})
     */
    private $fields;

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getFields()
    {
        return $this->fields;
    }
    /**
     * Get every node-type fields names in
     * a simple array.
     *
     * @return array
     */
    public function getFieldsNames()
    {
        $namesArray = [];

        foreach ($this->getFields() as $field) {
            $namesArray[] = $field->getName();
        }

        return $namesArray;
    }

    /**
     * @param NodeTypeField $field
     *
     * @return NodeType
     */
    public function addField(NodeTypeField $field)
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
    public function removeField(NodeTypeField $field)
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
    }

    /**
     * Get node-source entity class name without its namespace.
     *
     * @return string
     */
    public function getSourceEntityClassName()
    {
        return 'NS' . ucwords($this->getName());
    }

    /**
     * Get node-source entity database table name.
     *
     * @return string
     */
    public function getSourceEntityTableName()
    {
        return 'ns_' . strtolower($this->getName());
    }

    /**
     * @return string
     */
    public static function getGeneratedEntitiesNamespace()
    {
        return 'GeneratedNodeSources';
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return '[#' . $this->getId() . '] ' . $this->getName() . ' ('.$this->getDisplayName().')';
    }

    /**
     * Get every searchable node-type fields as a Doctrine ArrayCollection.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getSearchableFields()
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

    /**
     * @return  \RZ\Roadiz\Core\Handlers\NodeTypeHandler
     * @deprecated Use node_type.handler service.
     */
    public function getHandler()
    {
        return new NodeTypeHandler($this);
    }
}
