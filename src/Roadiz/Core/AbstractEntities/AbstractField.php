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
 *     @ORM\Index(columns={"position"})
 * })
 */
abstract class AbstractField extends AbstractEntity
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
     *
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
}
