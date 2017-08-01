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
 * @file AbstractField.php
 * @author Maxime Constantinian
 */
namespace RZ\Roadiz\Core\AbstractEntities;

use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Utils\StringHandler;

/**
 * @ORM\MappedSuperclass
 * @ORM\Table(indexes={
 *     @ORM\Index(columns={"position"}),
 *     @ORM\Index(columns={"group_name"}),
 *     @ORM\Index(columns={"group_name_canonical"})
 * })
 */
abstract class AbstractField extends AbstractEntity implements PositionedInterface
{
    use PositionedTrait;

    /**
     * String field is a simple 255 characters long text.
     */
    const STRING_T = 0;
    /**
     * DateTime field is a combined Date and Time.
     *
     * @see \DateTime
     */
    const DATETIME_T = 1;
    /**
     * Text field is a 65000 characters long text.
     */
    const TEXT_T = 2;
    /**
     * Richtext field is an HTML text using a WYSIWYG editor.
     *
     * Use Markdown type instead. WYSIWYG is evil.
     */
    const RICHTEXT_T = 3;
    /**
     * Markdown field is a pseudo-coded text which is render
     * with a simple editor.
     */
    const MARKDOWN_T = 4;
    /**
     * Boolean field is a simple switch between 0 and 1.
     */
    const BOOLEAN_T = 5;
    /**
     * Integer field is a non-floating number.
     */
    const INTEGER_T = 6;
    /**
     * Decimal field is a floating number.
     */
    const DECIMAL_T = 7;
    /**
     * Email field is a short text which must
     * comply with email rules.
     */
    const EMAIL_T = 8;
    /**
     * Documents field helps linking NodesSources with Documents.
     */
    const DOCUMENTS_T = 9;
    /**
     * Password field is a simple text data rendered
     * as a password input with a confirmation.
     */
    const PASSWORD_T = 10;
    /**
     * Colour field is an hexadecimal string which is rendered
     * with a colour chooser.
     */
    const COLOUR_T = 11;
    /**
     * Geotag field is a Map widget which stores
     * a Latitude and Longitude as an array.
     */
    const GEOTAG_T = 12;
    /**
     * Nodes field helps linking Nodes with other Nodes entities.
     */
    const NODES_T = 13;
    /**
     * Nodes field helps linking NodesSources with Users entities.
     */
    const USER_T = 14;
    /**
     * Enum field is a simple select box with default values.
     */
    const ENUM_T = 15;
    /**
     * Children field is a virtual field, it will only display a
     * NodeTreeWidget to show current Node children.
     */
    const CHILDREN_T = 16;
    /**
     * Nodes field helps linking Nodes with CustomForms entities.
     */
    const CUSTOM_FORMS_T = 17;
    /**
     * Multiple field is a simple select box with multiple choices.
     */
    const MULTIPLE_T = 18;
    /**
     * Radio group field is like ENUM_T but rendered as a radio
     * button group.
     */
    const RADIO_GROUP_T = 19;
    /**
     * Check group field is like MULTIPLE_T but rendered as
     * a checkbox group.
     */
    const CHECK_GROUP_T = 20;
    /**
     * Multi-Geotag field is a Map widget which stores
     * multiple Latitude and Longitude with names and icon options.
     */
    const MULTI_GEOTAG_T = 21;
    /**
     * @see \DateTime
     */
    const DATE_T = 22;
    /**
     * Textarea to write Json syntaxed code
     */
    const JSON_T = 23;
    /**
     * Textarea to write CSS syntaxed code
     */
    const CSS_T = 24;
    /**
     * Selectbox to choose ISO Country
     */
    const COUNTRY_T = 25;
    /**
     * Textarea to write YAML syntaxed text
     */
    const YAML_T = 26;
    /**
     * «Many to many» join to a custom doctrine entity class.
     */
    const MANY_TO_MANY_T = 27;
    /**
     * «Many to one» join to a custom doctrine entity class.
     */
    const MANY_TO_ONE_T = 28;

    /**
     * Associates abstract field type to a readable string.
     *
     * These string will be used as translation key.
     *
     * @var array
     */
    public static $typeToHuman = [
        AbstractField::STRING_T => 'string.type',
        AbstractField::DATETIME_T => 'date-time.type',
        AbstractField::DATE_T => 'date.type',
        AbstractField::TEXT_T => 'text.type',
        AbstractField::MARKDOWN_T => 'markdown.type',
        AbstractField::BOOLEAN_T => 'boolean.type',
        AbstractField::INTEGER_T => 'integer.type',
        AbstractField::DECIMAL_T => 'decimal.type',
        AbstractField::EMAIL_T => 'email.type',
        AbstractField::ENUM_T => 'single-choice.type',
        AbstractField::MULTIPLE_T => 'multiple-choice.type',
        AbstractField::DOCUMENTS_T => 'documents.type',
        AbstractField::NODES_T => 'nodes.type',
        AbstractField::CHILDREN_T => 'children-nodes.type',
        AbstractField::COLOUR_T => 'colour.type',
        AbstractField::GEOTAG_T => 'geographic.coordinates.type',
        AbstractField::CUSTOM_FORMS_T => 'custom-forms.type',
        AbstractField::MULTI_GEOTAG_T => 'multiple.geographic.coordinates.type',
        AbstractField::JSON_T => 'json.type',
        AbstractField::CSS_T => 'css.type',
        AbstractField::COUNTRY_T => 'country.type',
        AbstractField::YAML_T => 'yaml.type',
        AbstractField::MANY_TO_MANY_T => 'many-to-many.type',
        AbstractField::MANY_TO_ONE_T => 'many-to-one.type',
    ];
    /**
     * Associates abstract field type to a Doctrine type.
     *
     * @var array
     */
    public static $typeToDoctrine = [
        AbstractField::STRING_T => 'string',
        AbstractField::DATETIME_T => 'datetime',
        AbstractField::DATE_T => 'datetime',
        AbstractField::RICHTEXT_T => 'text',
        AbstractField::TEXT_T => 'text',
        AbstractField::MARKDOWN_T => 'text',
        AbstractField::BOOLEAN_T => 'boolean',
        AbstractField::INTEGER_T => 'integer',
        AbstractField::DECIMAL_T => 'decimal',
        AbstractField::EMAIL_T => 'string',
        AbstractField::ENUM_T => 'string',
        AbstractField::MULTIPLE_T => 'simple_array',
        AbstractField::DOCUMENTS_T => null,
        AbstractField::NODES_T => null,
        AbstractField::CHILDREN_T => null,
        AbstractField::COLOUR_T => 'string',
        AbstractField::GEOTAG_T => 'string',
        AbstractField::CUSTOM_FORMS_T => null,
        AbstractField::MULTI_GEOTAG_T => 'text',
        AbstractField::JSON_T => 'text',
        AbstractField::CSS_T => 'text',
        AbstractField::COUNTRY_T => 'string',
        AbstractField::YAML_T => 'text',
        AbstractField::MANY_TO_MANY_T => null,
        AbstractField::MANY_TO_ONE_T => null,
    ];
    /**
     * Associates abstract field type to a Symfony Form type.
     *
     * @var array
     */
    public static $typeToForm = [
        AbstractField::STRING_T => 'text',
        AbstractField::DATETIME_T => 'datetime',
        AbstractField::DATE_T => 'date',
        AbstractField::RICHTEXT_T => 'textarea',
        AbstractField::TEXT_T => 'textarea',
        AbstractField::MARKDOWN_T => 'markdown',
        AbstractField::BOOLEAN_T => 'checkbox',
        AbstractField::INTEGER_T => 'integer',
        AbstractField::DECIMAL_T => 'number',
        AbstractField::EMAIL_T => 'email',
        AbstractField::ENUM_T => 'enumeration',
        AbstractField::MULTIPLE_T => 'multiple_enumeration',
        AbstractField::DOCUMENTS_T => 'documents',
        AbstractField::NODES_T => 'referenced_nodes',
        AbstractField::CHILDREN_T => 'children_nodes',
        AbstractField::COLOUR_T => 'text',
        AbstractField::GEOTAG_T => 'text',
        AbstractField::MULTI_GEOTAG_T => 'text',
        AbstractField::CUSTOM_FORMS_T => 'custom_forms',
        AbstractField::JSON_T => 'json_text',
        AbstractField::CSS_T => 'css_text',
        AbstractField::COUNTRY_T => 'country',
        AbstractField::YAML_T => 'yaml_text',
        AbstractField::MANY_TO_MANY_T => 'referenced_entity',
        AbstractField::MANY_TO_ONE_T => 'referenced_entity',
    ];

    /**
     * List searchable fields types in a searchEngine such as Solr.
     *
     * @var array
     */
    protected static $searchableTypes = [
        AbstractField::STRING_T,
        AbstractField::RICHTEXT_T,
        AbstractField::TEXT_T,
        AbstractField::MARKDOWN_T,
    ];

    /**
     * @ORM\Column(type="string")
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
     * @return string
     */
    public function getGetterName()
    {
        return StringHandler::camelCase('get ' . $this->getName());
    }

    /**
     * @return string
     */
    public function getSetterName()
    {
        return StringHandler::camelCase('set ' . $this->getName());
    }

    /**
     * @ORM\Column(type="string")
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
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    private $placeholder;

    /**
     * @return mixed
     */
    public function getPlaceholder()
    {
        return $this->placeholder;
    }

    /**
     * @param mixed $placeholder
     * @return AbstractField
     */
    public function setPlaceholder($placeholder)
    {
        $this->placeholder = $placeholder;
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
     * @ORM\Column(name="default_values", type="text", nullable=true)
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
     * @ORM\Column(type="integer")
     */
    private $type = AbstractField::STRING_T;

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
     * @ORM\Column(name="group_name", type="string", nullable=true)
     */
    protected $groupName;

    /**
     * @ORM\Column(name="group_name_canonical", type="string", nullable=true)
     */
    protected $groupNameCanonical;

    /**
     * Gets the value of groupName.
     *
     * @return string
     */
    public function getGroupName()
    {
        return $this->groupName;
    }

    /**
     * @return mixed
     */
    public function getGroupNameCanonical()
    {
        return $this->groupNameCanonical;
    }

    /**
     * Sets the value of groupName.
     *
     * @param string $groupName the group name
     * @return self
     */
    public function setGroupName($groupName)
    {
        $this->groupName = trim(strip_tags($groupName));
        $this->groupNameCanonical = StringHandler::slugify($this->getGroupName());
        return $this;
    }

    /**
     * If current field data should be expanded (for choices and country types).
     *
     * @var bool
     * @ORM\Column(name="expanded", type="boolean", nullable=false, options={"default" = false})
     */
    private $expanded = false;

    /**
     * @return bool
     */
    public function isExpanded()
    {
        return $this->expanded;
    }

    /**
     * @param bool $expanded
     * @return AbstractField
     */
    public function setExpanded($expanded)
    {
        $this->expanded = $expanded;
        return $this;
    }

    /**
     * @return bool
     */
    public function isString()
    {
        return $this->getType() === static::STRING_T;
    }

    /**
     * @return bool
     */
    public function isText()
    {
        return $this->getType() === static::TEXT_T;
    }

    /**
     * @return bool
     */
    public function isDate()
    {
        return $this->getType() === static::DATE_T;
    }

    /**
     * @return bool
     */
    public function isDateTime()
    {
        return $this->getType() === static::DATETIME_T;
    }

    /**
     * @return bool
     */
    public function isRichText()
    {
        return $this->getType() === static::RICHTEXT_T;
    }

    /**
     * @return bool
     */
    public function isMarkdown()
    {
        return $this->getType() === static::MARKDOWN_T;
    }

    /**
     * @return bool
     */
    public function isBoolean()
    {
        return $this->getType() === static::BOOLEAN_T;
    }

    /**
     * @return bool
     */
    public function isBool()
    {
        return $this->isBoolean();
    }

    /**
     * @return bool
     */
    public function isInteger()
    {
        return $this->getType() === static::INTEGER_T;
    }

    /**
     * @return bool
     */
    public function isDecimal()
    {
        return $this->getType() === static::DECIMAL_T;
    }

    /**
     * @return bool
     */
    public function isEmail()
    {
        return $this->getType() === static::EMAIL_T;
    }

    /**
     * @return bool
     */
    public function isDocuments()
    {
        return $this->getType() === static::DOCUMENTS_T;
    }

    /**
     * @return bool
     */
    public function isPassword()
    {
        return $this->getType() === static::PASSWORD_T;
    }

    /**
     * @return bool
     */
    public function isColour()
    {
        return $this->getType() === static::COLOUR_T;
    }
    /**
     * @return bool
     */
    public function isColor()
    {
        return $this->isColour();
    }

    /**
     * @return bool
     */
    public function isGeoTag()
    {
        return $this->getType() === static::GEOTAG_T;
    }

    /**
     * @return bool
     */
    public function isNodes()
    {
        return $this->getType() === static::NODES_T;
    }

    /**
     * @return bool
     */
    public function isUser()
    {
        return $this->getType() === static::USER_T;
    }

    /**
     * @return bool
     */
    public function isEnum()
    {
        return $this->getType() === static::ENUM_T;
    }

    /**
     * @return bool
     */
    public function isChildrenNodes()
    {
        return $this->getType() === static::CHILDREN_T;
    }

    /**
     * @return bool
     */
    public function isCustomForms()
    {
        return $this->getType() === static::CUSTOM_FORMS_T;
    }

    /**
     * @return bool
     */
    public function isMultiple()
    {
        return $this->getType() === static::MULTIPLE_T;
    }

    /**
     * @return bool
     */
    public function isMultiGeoTag()
    {
        return $this->getType() === static::MULTI_GEOTAG_T;
    }

    /**
     * @return bool
     */
    public function isJson()
    {
        return $this->getType() === static::JSON_T;
    }

    /**
     * @return bool
     */
    public function isYaml()
    {
        return $this->getType() === static::YAML_T;
    }

    /**
     * @return bool
     */
    public function isCss()
    {
        return $this->getType() === static::CSS_T;
    }

    /**
     * @return bool
     */
    public function isManyToMany()
    {
        return $this->getType() === static::MANY_TO_MANY_T;
    }

    /**
     * @return bool
     */
    public function isManyToOne()
    {
        return $this->getType() === static::MANY_TO_ONE_T;
    }

    /**
     * @return bool
     */
    public function isCountry()
    {
        return $this->getType() === static::COUNTRY_T;
    }
}
