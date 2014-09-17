<?php
/*
 * Copyright REZO ZERO 2014
 *
 * @file NodeType.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\AbstractEntity;
use RZ\Renzo\Core\Handlers\NodeTypeHandler;
use RZ\Renzo\Core\Serializers\NodeTypeSerializer;
use RZ\Renzo\Core\Utils\StringHandler;

/**
 * NodeTypes describe each node structure family,
 * They are mandatory before creating any Node.
 *
 * @Entity(repositoryClass="RZ\Renzo\Core\Entities\NodeTypeRepository")
 * @Table(name="node_types", indexes={
 *     @index(name="visible_idx",         columns={"visible"}),
 *     @index(name="newsletter_type_idx", columns={"newsletter_type"}),
 *     @index(name="hiding_nodes_idx",    columns={"hiding_nodes"})
 * })
 */
class NodeType extends AbstractEntity
{
    /**
     * @Column(type="string", unique=true)
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
     * @Column(name="display_name", type="string")
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
     * @Column(type="text", nullable=true)
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
     * @Column(type="boolean")
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
     * @Column(name="newsletter_type", type="boolean")
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
     * @Column(name="hiding_nodes",type="boolean")
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
     * @OneToMany(targetEntity="NodeTypeField", mappedBy="nodeType", cascade={"ALL"})
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
     * @return  RZ\Renzo\Core\Handlers\NodeTypeHandler
     */
    public function getHandler()
    {
        return new NodeTypeHandler($this);
    }
}
