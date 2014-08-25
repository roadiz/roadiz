<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file NodeTypeField.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Entities;

use RZ\Renzo\Core\AbstractEntities\PersistableInterface;
use RZ\Renzo\Core\AbstractEntities\AbstractPositioned;
use RZ\Renzo\Core\Utils\StringHandler;
use RZ\Renzo\Core\Handlers\NodeTypeFieldHandler;
use RZ\Renzo\Core\Serializers\NodeTypeFieldSerializer;

/**
 * NodeTypeField entities are used to create NodeTypes with
 * custom data structure.
 *
 * @Entity(repositoryClass="RZ\Renzo\Core\Utils\EntityRepository")
 * @Table(name="node_type_fields",  indexes={
 *     @index(name="visible_idx", columns={"visible"}),
 *     @index(name="indexed_idx", columns={"indexed"})
 * },
 * uniqueConstraints={@UniqueConstraint(columns={"name", "node_type_id"})})
 */
class NodeTypeField extends AbstractPositioned implements PersistableInterface
{
    /**
     * String field is a simple 255 characters long text.
     */
    const STRING_T =        0;
    /**
     * DateTime field is a combined Date and Time.
     *
     * @see \DateTime
     */
    const DATETIME_T =      1;
    /**
     * Text field is a 65000 characters long text.
     */
    const TEXT_T =          2;
    /**
     * Richtext field is an HTML text using a WYSIWYG editor.
     *
     * @deprecated Use Markdown type instead. WYSIWYG is evil.
     */
    const RICHTEXT_T =      3;
    /**
     * Markdown field is a pseudo-coded text which is render
     * with a simple editor.
     */
    const MARKDOWN_T =      4;
    /**
     * Boolean field is a simple switch between 0 and 1.
     */
    const BOOLEAN_T =       5;
    /**
     * Integer field is a non-floating number.
     */
    const INTEGER_T =       6;
    /**
     * Decimal field is a floating number.
     */
    const DECIMAL_T =       7;
    /**
     * Email field is a short text which must
     * comply with email rules.
     */
    const EMAIL_T =         8;
    /**
     * Documents field helps linking NodesSources with Documents.
     */
    const DOCUMENTS_T =     9;
    /**
     * Password field is a simple text data rendered
     * as a password input with a confirmation.
     */
    const PASSWORD_T =      10;
    /**
     * Colour field is an hexadecimal string which is rendered
     * with a colour chooser.
     */
    const COLOUR_T =        11;
    /**
     * Geotag field is a Map widget which stores
     * a Latitude and Longitude as an array.
     */
    const GEOTAG_T =        12;
    /**
     * Nodes field helps linking NodesSources with other Nodes entities.
     */
    const NODE_T =          13;
    /**
     * Nodes field helps linking NodesSources with Users entities.
     */
    const USER_T =          14;
    /**
     * Enum field is a simple select box with default values.
     */
    const ENUM_T =          15;
    /**
     * Children field is a virtual field, it will only display a
     * NodeTreeWidget to show current Node children.
     */
    const CHILDREN_T =      16;
    /**
     * Nodes field helps linking NodesSources with Surveys entities.
     */
    const SURVEY_T =        17;
    /**
     * Multiple field is a simple select box with multiple choices.
     */
    const MULTIPLE_T =      18;
    /**
     * Radio group field is like ENUM_T but rendered as a radio
     * button group.
     */
    const RADIO_GROUP_T =   19;
    /**
     * Check group field is like MULTIPLE_T but rendered as
     * a checkbox group.
     */
    const CHECK_GROUP_T =   20;
    /**
     * Multi-Geotag field is a Map widget which stores
     * multiple Latitude and Longitude with names and icon options.
     */
    const MULTI_GEOTAG_T =  21;

    /**
     * Associates node-type field type to a readable string.
     *
     * @var array
     */
    public static $typeToHuman = array(
        NodeTypeField::STRING_T =>   'string',
        NodeTypeField::DATETIME_T => 'date-time',
        NodeTypeField::TEXT_T =>     'text',
        NodeTypeField::MARKDOWN_T => 'markdown',
        NodeTypeField::BOOLEAN_T =>  'boolean',
        NodeTypeField::INTEGER_T =>  'integer',
        NodeTypeField::DECIMAL_T =>  'decimal',
        NodeTypeField::EMAIL_T =>    'email',
        NodeTypeField::ENUM_T =>     'single-choice',
        NodeTypeField::MULTIPLE_T => 'multiple-choice',
        NodeTypeField::DOCUMENTS_T =>'documents',
    );
    /**
     * Associates node-type field type to a Doctrine type.
     *
     * @var array
     */
    public static $typeToDoctrine = array(
        NodeTypeField::STRING_T =>   'string',
        NodeTypeField::DATETIME_T => 'datetime',
        NodeTypeField::RICHTEXT_T => 'text',
        NodeTypeField::TEXT_T =>     'text',
        NodeTypeField::MARKDOWN_T => 'text',
        NodeTypeField::BOOLEAN_T =>  'boolean',
        NodeTypeField::INTEGER_T =>  'integer',
        NodeTypeField::DECIMAL_T =>  'decimal',
        NodeTypeField::EMAIL_T =>    'string',
        NodeTypeField::ENUM_T =>     'string',
        NodeTypeField::MULTIPLE_T => 'simple_array',
        NodeTypeField::DOCUMENTS_T => null,
    );
    /**
     * Associates node-type field type to a Symfony Form type.
     *
     * @var array
     */
    public static $typeToForm = array(
        NodeTypeField::STRING_T =>   'text',
        NodeTypeField::DATETIME_T => 'datetime',
        NodeTypeField::RICHTEXT_T => 'textarea',
        NodeTypeField::TEXT_T =>     'textarea',
        NodeTypeField::MARKDOWN_T => 'markdown',
        NodeTypeField::BOOLEAN_T =>  'checkbox',
        NodeTypeField::INTEGER_T =>  'integer',
        NodeTypeField::DECIMAL_T =>  'decimal',
        NodeTypeField::EMAIL_T =>    'email',
        NodeTypeField::ENUM_T =>     'enumeration',
        NodeTypeField::MULTIPLE_T => 'multiple_enumeration',
        NodeTypeField::DOCUMENTS_T =>'documents',
    );


    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private $id;
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @ManyToOne(targetEntity="NodeType", inversedBy="fields")
     * @JoinColumn(name="node_type_id", onDelete="CASCADE")
     */
    private $nodeType;

    /**
     * @return RZ\Renzo\Core\Entities\NodeType
     */
    public function getNodeType()
    {
        return $this->nodeType;
    }

    /**
     * @param RZ\Renzo\Core\Entities\NodeType $nodeType
     *
     * @return $this
     */
    public function setNodeType($nodeType)
    {
        $this->nodeType = $nodeType;

        return $this;
    }

    /**
     * @Column(type="string")
     */
    private $name;

    /**
     * @return string $name
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
        $this->name = StringHandler::variablize($name);

        return $this;
    }
    /**
     * @Column(type="string")
     */
    private $label;

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     *
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = $label;

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
     * @Column(type="text", nullable=true)
     */
    private $defaultValues;

    /**
     * @return string
     */
    public function getDefaultValues()
    {
        return $this->defaultValues;
    }

    /**
     * @param string $defaultValues
     *
     * @return $this
     */
    public function setDefaultValues($defaultValues)
    {
        $this->defaultValues = $defaultValues;

        return $this;
    }
    /**
     * @Column(type="boolean")
     */
    private $indexed = false;

    /**
     * @return boolean $isIndexed
     */
    public function isIndexed()
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
     * @Column(type="boolean")
     */
    private $visible = true;

    /**
     * @return boolean $isVisible
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
     * @Column(type="integer")
     */
    private $type = NodeTypeField::STRING_T;

    /**
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getTypeName()
    {
        return static::$typeToHuman[$this->type];
    }

    /**
     * @param integer $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = (int) $type;

        return $this;
    }

    /**
     * @return boolean Is node type field virtual, it's just an association, no doctrine field created
     */
    public function isVirtual()
    {
        return static::$typeToDoctrine[$this->getType()] === null ? true : false;
    }

    /**
     * @return string
     */
    public function getGetterName()
    {
        return 'get'.str_replace('_', '', ucwords($this->getName()));
    }

    /**
     * @return string
     */
    public function getSetterName()
    {
        return 'set'.str_replace('_', '', ucwords($this->getName()));
    }

    /**
     * @return RZ\Renzo\Core\Handlers\NodeTypeFieldHandler
     */
    public function getHandler()
    {
        return new NodeTypeFieldHandler($this);
    }

    /**
     * @todo Move this method to a NodeTypeFieldViewer
     * @return string
     */
    public function getOneLineSummary()
    {
        return $this->getId()." — ".$this->getName()." — ".$this->getLabel().
            " — Indexed : ".($this->isIndexed()?'true':'false').PHP_EOL;
    }
}