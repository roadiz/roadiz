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
 * @file NodeTypeField.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Entities;

use RZ\Roadiz\Core\AbstractEntities\AbstractField;
use RZ\Roadiz\Core\Utils\StringHandler;
use RZ\Roadiz\Core\Handlers\NodeTypeFieldHandler;
use RZ\Roadiz\Core\Serializers\NodeTypeFieldSerializer;

/**
 * NodeTypeField entities are used to create NodeTypes with
 * custom data structure.
 *
 * @Entity(repositoryClass="RZ\Roadiz\Core\Repositories\EntityRepository")
 * @Table(name="node_type_fields",  indexes={
 *         @index(name="visible_nodetypefield_idx",  columns={"visible"}),
 *         @index(name="indexed_nodetypefield_idx",  columns={"indexed"}),
 *         @index(name="position_nodetypefield_idx", columns={"position"}),
 *         @index(name="type_nodetypefield_idx",     columns={"type"})
 *     },
 *     uniqueConstraints={@UniqueConstraint(columns={"name", "node_type_id"})}
 * )
 * @HasLifecycleCallbacks
 */
class NodeTypeField extends AbstractField
{
    /**
     * Associates node-type field type to a readable string.
     *
     * These string will be used as translation key.
     *
     * @var array
     */
    public static $typeToHuman = array(
        AbstractField::STRING_T =>    'string.type',
        AbstractField::DATETIME_T =>  'date-time.type',
        AbstractField::TEXT_T =>      'text.type',
        AbstractField::MARKDOWN_T =>  'markdown.type',
        AbstractField::BOOLEAN_T =>   'boolean.type',
        AbstractField::INTEGER_T =>   'integer.type',
        AbstractField::DECIMAL_T =>   'decimal.type',
        AbstractField::EMAIL_T =>     'email.type',
        AbstractField::ENUM_T =>      'single-choice.type',
        AbstractField::MULTIPLE_T =>  'multiple-choice.type',
        AbstractField::DOCUMENTS_T => 'documents.type',
        AbstractField::NODES_T =>     'nodes.type',
        AbstractField::CHILDREN_T =>  'children-nodes.type',
        AbstractField::COLOUR_T =>    'colour.type',
        AbstractField::GEOTAG_T =>    'geographic.coordinates.type',
    );
    /**
     * Associates node-type field type to a Doctrine type.
     *
     * @var array
     */
    public static $typeToDoctrine = array(
        AbstractField::STRING_T =>     'string',
        AbstractField::DATETIME_T =>   'datetime',
        AbstractField::RICHTEXT_T =>   'text',
        AbstractField::TEXT_T =>       'text',
        AbstractField::MARKDOWN_T =>   'text',
        AbstractField::BOOLEAN_T =>    'boolean',
        AbstractField::INTEGER_T =>    'integer',
        AbstractField::DECIMAL_T =>    'decimal',
        AbstractField::EMAIL_T =>      'string',
        AbstractField::ENUM_T =>       'string',
        AbstractField::MULTIPLE_T =>   'simple_array',
        AbstractField::DOCUMENTS_T =>  null,
        AbstractField::NODES_T =>      null,
        AbstractField::CHILDREN_T =>   null,
        AbstractField::COLOUR_T =>     'string',
        AbstractField::GEOTAG_T =>     'string',
    );
    /**
     * Associates node-type field type to a Symfony Form type.
     *
     * @var array
     */
    public static $typeToForm = array(
        AbstractField::STRING_T =>    'text',
        AbstractField::DATETIME_T =>  'datetime',
        AbstractField::RICHTEXT_T =>  'textarea',
        AbstractField::TEXT_T =>      'textarea',
        AbstractField::MARKDOWN_T =>  'markdown',
        AbstractField::BOOLEAN_T =>   'checkbox',
        AbstractField::INTEGER_T =>   'integer',
        AbstractField::DECIMAL_T =>   'number',
        AbstractField::EMAIL_T =>     'email',
        AbstractField::ENUM_T =>      'enumeration',
        AbstractField::MULTIPLE_T =>  'multiple_enumeration',
        AbstractField::DOCUMENTS_T => 'documents',
        AbstractField::NODES_T =>     'referenced_nodes',
        AbstractField::CHILDREN_T =>  'children_nodes',
        AbstractField::COLOUR_T =>    'text',
        AbstractField::GEOTAG_T =>    'text',
    );

    /**
     * List searchable fields types in a searchEngine such as Solr.
     *
     * @var array
     */
    protected static $searchableTypes = array(
        AbstractField::STRING_T,
        AbstractField::RICHTEXT_T,
        AbstractField::TEXT_T,
        AbstractField::MARKDOWN_T,
    );

    /**
     * @ManyToOne(targetEntity="NodeType", inversedBy="fields")
     * @JoinColumn(name="node_type_id", onDelete="CASCADE")
     */
    private $nodeType;

    /**
     * @return RZ\Roadiz\Core\Entities\NodeType
     */
    public function getNodeType()
    {
        return $this->nodeType;
    }

    /**
     * @param RZ\Roadiz\Core\Entities\NodeType $nodeType
     *
     * @return $this
     */
    public function setNodeType($nodeType)
    {
        $this->nodeType = $nodeType;

        return $this;
    }

    /**
     * @Column(name="min_length", type="integer", nullable=true)
     */
    private $minLength = null;

    /**
     * @return int
     */
    public function getMinLength()
    {
        return $this->minLength;
    }

    /**
     * @param int $minValue
     *
     * @return $this
     */
    public function setMinLength($minLength)
    {
        $this->minLength = $minLength;

        return $this;
    }

    /**
     * @Column(name="max_length", type="integer", nullable=true)
     */
    private $maxLength = null;

    /**
     * @return int
     */
    public function getMaxLength()
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
     * @return RZ\Roadiz\Core\Handlers\NodeTypeFieldHandler
     */
    public function getHandler()
    {
        return new NodeTypeFieldHandler($this);
    }

    /**
     * Tell if current field can be searched and indexed in a Search engine server.
     *
     * @return boolean
     */
    public function isSearchable()
    {
        return (boolean) in_array($this->getType(), static::$searchableTypes);
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
     * @todo Move this method to a NodeTypeFieldViewer
     * @return string
     */
    public function getOneLineSummary()
    {
        return $this->getId()." — ".$this->getName()." — ".$this->getLabel().
            " — Indexed : ".($this->isIndexed()?'true':'false').PHP_EOL;
    }
}
