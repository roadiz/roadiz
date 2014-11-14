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
 * @file NodeTypeFieldHandler.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Handlers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\Entities\NodeTypeField;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Handlers\NodeTypeHandler;
use RZ\Renzo\Core\Serializers\NodeTypeFieldSerializer;

/**
 * Handle operations with node-type fields entities.
 */
class NodeTypeFieldHandler
{

    private $nodeTypeField = null;
    /**
     * @return NodeTypeField
     */
    public function getNodeTypeField()
    {
        return $this->nodeTypeField;
    }
    /**
     * @param NodeTypeField $nodeTypeField
     *
     * @return $this
     */
    public function setNodeTypeField($nodeTypeField)
    {
        $this->nodeTypeField = $nodeTypeField;

        return $this;
    }

    /**
     * Generate PHP annotation block for table indexes.
     *
     * @return string
     */
    public function generateSourceFieldIndex()
    {
        if (NodeTypeField::$typeToDoctrine[$this->nodeTypeField->getType()] !== null) {
            return '@Index(name="'.$this->nodeTypeField->getName().'_idx", columns={"'.$this->nodeTypeField->getName().'"})';
        } else {
            return '';
        }
    }

    /**
     * Generate PHP code for current node-type field.
     *
     * @return string
     */
    public function generateSourceField()
    {
        if (NodeTypeField::$typeToDoctrine[$this->nodeTypeField->getType()] !== null) {
            $var = 'private $'.$this->nodeTypeField->getName().';';
            if ($this->nodeTypeField->getType() === NodeTypeField::BOOLEAN_T) {
                $var = 'private $'.$this->nodeTypeField->getName().' = false;';
            }
            if ($this->nodeTypeField->getType() === NodeTypeField::INTEGER_T) {
                $var = 'private $'.$this->nodeTypeField->getName().' = 0;';
            }

            return '
                /**
                 * @Column(type="'.
                    NodeTypeField::$typeToDoctrine[$this->nodeTypeField->getType()].
                    '", '.
                    $this->getDecimalPrecision().
                    'nullable=true )
                 */
                '.$var.PHP_EOL.$this->generateSourceGetter().PHP_EOL.$this->generateSourceSetter().PHP_EOL;
        }

        return '';
    }

    protected function getDecimalPrecision()
    {
        if ($this->nodeTypeField->getType() == NodeTypeField::DECIMAL_T) {
            return 'precision=10, scale=3, ';
        } else {
            return '';
        }
    }

    /**
     * Generate PHP code for current node-type field setter.
     *
     * @return string
     */
    protected function generateSourceSetter()
    {
        if (NodeTypeField::$typeToDoctrine[$this->nodeTypeField->getType()] !== null) {

            $assignation = '$'.$this->nodeTypeField->getName();

            if ($this->nodeTypeField->getType() === NodeTypeField::BOOLEAN_T) {
                $assignation = '(boolean) $'.$this->nodeTypeField->getName();
            }
            if ($this->nodeTypeField->getType() === NodeTypeField::INTEGER_T) {
                $assignation = '(int) $'.$this->nodeTypeField->getName();
            }
            if ($this->nodeTypeField->getType() === NodeTypeField::DECIMAL_T) {
                $assignation = '(double) $'.$this->nodeTypeField->getName();
            }

            return '
    /**
     * @param mixed $'.$this->nodeTypeField->getName().'
     *
     * @return $this
     */
    public function '.$this->nodeTypeField->getSetterName().'($'.$this->nodeTypeField->getName().')
    {
        $this->'.$this->nodeTypeField->getName().' = '.$assignation.';

        return $this;
    }'.PHP_EOL;

        }

        return '';
    }

    /**
     * Generate PHP code for current node-type field setter.
     *
     * @return string
     */
    protected function generateSourceGetter()
    {
        if (NodeTypeField::$typeToDoctrine[$this->nodeTypeField->getType()] !== null) {

            $assignation = '$this->'.$this->nodeTypeField->getName();

            return '
    /**
     * @return mixed
     */
    public function '.$this->nodeTypeField->getGetterName().'()
    {
        return '.$assignation.';
    }'.PHP_EOL;

        }

        return '';
    }

    /**
     * Create a new node-type-field handler with node-type-field to handle.
     *
     * @param NodeTypeField $field
     */
    public function __construct(NodeTypeField $field)
    {
        $this->nodeTypeField = $field;
    }

    /**
     * Clean position for current node siblings.
     *
     * @return int Return the next position after the **last** node
     */
    public function cleanPositions()
    {
        if ($this->nodeTypeField->getNodeType() !== null) {
            return $this->nodeTypeField->getNodeType()->getHandler()->cleanFieldsPositions();
        }
    }
}
