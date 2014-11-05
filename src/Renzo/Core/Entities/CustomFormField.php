<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file CustomFormField.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Entities;

use RZ\Renzo\Core\AbstractEntities\PersistableInterface;
use RZ\Renzo\Core\AbstractEntities\AbstractPositioned;
use RZ\Renzo\Core\Handlers\CustomFormFieldHandler;
use RZ\Renzo\Core\Utils\StringHandler;

/**
 * CustomFormField entities are used to create CustomForms with
 * custom data structure.
 *
 * @Entity(repositoryClass="RZ\Renzo\Core\Repositories\EntityRepository")
 * @Table(name="custom_form_fields",
 * uniqueConstraints={@UniqueConstraint(columns={"name", "custom_form_id"})})
 * @HasLifecycleCallbacks
 */
class CustomFormField extends AbstractPositioned implements PersistableInterface
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
     * These string will be used as translation key.
     *
     * @var array
     */
    public static $typeToHuman = array(
        CustomFormField::STRING_T =>   'string.type',
        CustomFormField::DATETIME_T => 'date-time.type',
        CustomFormField::TEXT_T =>     'text.type',
        CustomFormField::MARKDOWN_T => 'markdown.type',
        CustomFormField::BOOLEAN_T =>  'boolean.type',
        CustomFormField::INTEGER_T =>  'integer.type',
        CustomFormField::DECIMAL_T =>  'decimal.type',
        CustomFormField::EMAIL_T =>    'email.type',
        CustomFormField::ENUM_T =>     'single-choice.type',
        CustomFormField::MULTIPLE_T => 'multiple-choice.type',
        CustomFormField::DOCUMENTS_T =>'documents.type',
        CustomFormField::CHILDREN_T => 'children-nodes.type',
    );
    /**
     * Associates node-type field type to a Doctrine type.
     *
     * @var array
     */
    public static $typeToDoctrine = array(
        CustomFormField::STRING_T =>   'string',
        CustomFormField::DATETIME_T => 'datetime',
        CustomFormField::RICHTEXT_T => 'text',
        CustomFormField::TEXT_T =>     'text',
        CustomFormField::MARKDOWN_T => 'text',
        CustomFormField::BOOLEAN_T =>  'boolean',
        CustomFormField::INTEGER_T =>  'integer',
        CustomFormField::DECIMAL_T =>  'decimal',
        CustomFormField::EMAIL_T =>    'string',
        CustomFormField::ENUM_T =>     'string',
        CustomFormField::MULTIPLE_T => 'simple_array',
        CustomFormField::DOCUMENTS_T => null,
        CustomFormField::CHILDREN_T =>  null,
    );
    /**
     * Associates node-type field type to a Symfony Form type.
     *
     * @var array
     */
    public static $typeToForm = array(
        CustomFormField::STRING_T =>   'text',
        CustomFormField::DATETIME_T => 'datetime',
        CustomFormField::RICHTEXT_T => 'textarea',
        CustomFormField::TEXT_T =>     'textarea',
        CustomFormField::MARKDOWN_T => 'markdown',
        CustomFormField::BOOLEAN_T =>  'checkbox',
        CustomFormField::INTEGER_T =>  'integer',
        CustomFormField::DECIMAL_T =>  'number',
        CustomFormField::EMAIL_T =>    'email',
        CustomFormField::ENUM_T =>     'enumeration',
        CustomFormField::MULTIPLE_T => 'multiple_enumeration',
        CustomFormField::DOCUMENTS_T =>'documents',
        CustomFormField::CHILDREN_T => 'children_nodes',
    );

    /**
     * List searchable fields types in a searchEngine such as Solr.
     *
     * @var array
     */
    protected static $searchableTypes = array(
        CustomFormField::STRING_T,
        CustomFormField::RICHTEXT_T,
        CustomFormField::TEXT_T,
        CustomFormField::MARKDOWN_T,
    );

    /**
     * List of forbidden field names.
     *
     * These are SQL reserved words.
     *
     * @var array
     */
    public static $forbiddenNames = array(
        'title', 'order', 'integer', 'int', 'float', 'join',
        'inner', 'select', 'from', 'where', 'by', 'varchar',
        'text', 'enum', 'left', 'outer', 'blob'
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
     * @ManyToOne(targetEntity="RZ\Renzo\Core\Entities\CustomForm", inversedBy="fields")
     * @JoinColumn(name="custom_form_id", onDelete="CASCADE")
     */
    private $customForm;

    /**
     * @return RZ\Renzo\Core\Entities\CustomForm
     */
    public function getCustomForm()
    {
        return $this->customForm;
    }

    /**
     * @param RZ\Renzo\Core\Entities\CustomForm $customForm
     *
     * @return $this
     */
    public function setCustomForm($customForm)
    {
        $this->customForm = $customForm;

        return $this;
    }

    /**
     * @OneToMany(targetEntity="RZ\Renzo\Core\Entities\CustomFormFieldAttribute", mappedBy="customFormField")
     */
    private $customFormFieldAttribute;

    public function getCustomFormFieldAttribute()
    {
        return $this->customFormFieldAttribute;
    }

    public function __contruct()
    {
        $this->customFormFieldAttribute = new ArrayCollection();
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
     * @Column(type="integer")
     */
    private $type = CustomFormField::STRING_T;

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
     * @Column(type="boolean")
     */
    private $require = false;

    /**
     * @return boolean $isRequire
     */
    public function isRequire()
    {
        return $this->require;
    }

    /**
     * @param boolean $require
     *
     * @return $this
     */
    public function setRequire($require)
    {
        $this->require = $require;

        return $this;
    }

    /**
     * @return  RZ\Renzo\Core\Handlers\CustomFormFieldHandler
     */
    public function getHandler()
    {
        return new CustomFormFieldHandler($this);
    }

    /**
     * @PrePersist
     */
    public function prePersist()
    {
        /*
         * Get the last index after last node in parent
         */
        $this->setPosition($this->getHandler()->cleanPositions());
    }

    /**
     * @todo Move this method to a CustomFormFieldViewer
     * @return string
     */
    public function getOneLineSummary()
    {
        return $this->getId()." — ".$this->getName()." — ".$this->getLabel().PHP_EOL;
    }
}
