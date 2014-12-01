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
 * @file NodeType.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use RZ\Roadiz\Core\Handlers\NodeTypeHandler;
use RZ\Roadiz\Core\Utils\StringHandler;
use Doctrine\ORM\Mapping AS ORM;

/**
 * NodeTypes describe each node structure family,
 * They are mandatory before creating any Node.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\NodeTypeRepository")
 * @ORM\Table(name="node_types", indexes={
 *     @ORM\Index(name="visible_nodetype_idx",         columns={"visible"}),
 *     @ORM\Index(name="newsletter_type_nodetype_idx", columns={"newsletter_type"}),
 *     @ORM\Index(name="hiding_nodes_nodetype_idx",    columns={"hiding_nodes"})
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
     * @ORM\Column(type="boolean")
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
     * @ORM\Column(name="newsletter_type", type="boolean")
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
     * @ORM\Column(name="hiding_nodes",type="boolean")
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
     * @return Doctrine\Common\Collections\ArrayCollection
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
        $namesArray = array();

        foreach ($this->getFields() as $field) {
            $namesArray[] = $field->getName();
        }

        return $namesArray;
    }

    /**
     * @param NodeTypeField $field
     *
     * @return NodeTypeField
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
     * @return NodeTypeField
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
     * @return string
     */
    public function getSourceEntityClassName()
    {
        return 'NS'.ucwords($this->getName());
    }

    /**
     * @return string
     */
    public function getSourceEntityTableName()
    {
        return 'ns_'.strtolower($this->getName());
    }

    /**
     * @return string
     */
    public static function getGeneratedEntitiesNamespace()
    {
        return 'GeneratedNodeSources';
    }

    /**
     * @todo Move this method to a NodeTypeViewer
     * @return string
     */
    public function getOneLineSummary()
    {
        return $this->getId()." — ".$this->getName().
            " — Visible : ".($this->isVisible()?'true':'false').PHP_EOL;
    }

    /**
     * @todo Move this method to a NodeTypeViewer
     * @return string $text
     */
    public function getFieldsSummary()
    {
        $text = "|".PHP_EOL;
        foreach ($this->getFields() as $field) {
            $text .= "|--- ".$field->getOneLineSummary();
        }

        return $text;
    }

    /**
     * Get every searchable node-type fields as a Doctrine ArrayCollection.
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function getSearchableFields()
    {
        $searchable = new ArrayCollection();
        foreach ($this->getFields() as $field) {
            if ($field->isSearchable()) {
                $searchable->add($field);
            }
        }

        return $searchable;
    }

    /**
     * @return  RZ\Roadiz\Core\Handlers\NodeTypeHandler
     */
    public function getHandler()
    {
        return new NodeTypeHandler($this);
    }
}
