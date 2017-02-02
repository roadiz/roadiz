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
 * @file NodeTypeFieldHandler.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Handlers;

use RZ\Roadiz\Core\AbstractEntities\AbstractField;
use RZ\Roadiz\Core\Entities\NodeTypeField;

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
            return '@ORM\Index(columns={"'.$this->nodeTypeField->getName().'"})';
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
        return $this->getORMAnnotation().
               $this->getFieldDeclaration().
               $this->generateSourceGetter().
               $this->generateAlternativeGetter().
               $this->generateSourceSetter().PHP_EOL;
    }
    /**
     * @return string
     */
    protected function getORMAnnotation()
    {
        if (NodeTypeField::$typeToDoctrine[$this->nodeTypeField->getType()] !== null) {
            $ormParams = [
                'type' => '"' . NodeTypeField::$typeToDoctrine[$this->nodeTypeField->getType()] . '"',
                'nullable' => 'true',
                'name' => '"' . $this->nodeTypeField->getName() . '"',
            ];

            if ($this->nodeTypeField->getType() == NodeTypeField::DECIMAL_T) {
                $ormParams['precision'] = 18;
                $ormParams['scale'] = 3;
            } elseif ($this->nodeTypeField->getType() == NodeTypeField::BOOLEAN_T) {
                $ormParams['nullable'] = 'false';
                $ormParams['options'] = '{"default" = false}';
            }

            return '
    /**
     * ' . $this->nodeTypeField->getLabel() .'
     *
     * @ORM\Column(' . $this->flattenORMParameters($ormParams) . ')
     */'.PHP_EOL;
        } else {
            return '
    /**
     * ' . $this->nodeTypeField->getLabel() .'
     * (Virtual field, this var is a buffer)
     */'.PHP_EOL;
        }
    }

    protected function flattenORMParameters(array $ormParams)
    {
        $flatParams = [];
        foreach ($ormParams as $key => $value) {
            $flatParams[] = $key . '=' . $value;
        }

        return implode(', ', $flatParams);
    }

    /**
     * @return string
     */
    protected function getFieldDeclaration()
    {
        if (NodeTypeField::$typeToDoctrine[$this->nodeTypeField->getType()] !== null) {
            if ($this->nodeTypeField->getType() === NodeTypeField::BOOLEAN_T) {
                return '    private $'.$this->nodeTypeField->getName().' = false;'.PHP_EOL;
            } elseif ($this->nodeTypeField->getType() === NodeTypeField::INTEGER_T) {
                return '    private $'.$this->nodeTypeField->getName().' = 0;'.PHP_EOL;
            } else {
                return '    private $'.$this->nodeTypeField->getName().';'.PHP_EOL;
            }
        } else {
            /*
             * Buffer var to get referenced entities (documents, nodes, cforms)
             */
            return '    private $'.$this->nodeTypeField->getName().' = null;'.PHP_EOL;
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
        } elseif (AbstractField::DOCUMENTS_T === $this->nodeTypeField->getType()) {
            return '
    /**
     * @return array Documents array
     */
    public function '.$this->nodeTypeField->getGetterName().'()
    {
        if (null === $this->' . $this->nodeTypeField->getName() . ') {
            $this->' . $this->nodeTypeField->getName() . ' = $this->getHandler()->getDocumentsFromFieldName("'.$this->nodeTypeField->getName().'");
        }
        return $this->' . $this->nodeTypeField->getName() . ';
    }'.PHP_EOL;
        } elseif (AbstractField::NODES_T === $this->nodeTypeField->getType()) {
            return '
    /**
     * @return array Node array
     */
    public function '.$this->nodeTypeField->getGetterName().'()
    {
        if (null === $this->' . $this->nodeTypeField->getName() . ') {
            $this->' . $this->nodeTypeField->getName() . ' = $this->getHandler()->getNodesFromFieldName("'.$this->nodeTypeField->getName().'");
        }
        return $this->' . $this->nodeTypeField->getName() . ';
    }'.PHP_EOL;
        } elseif (AbstractField::CUSTOM_FORMS_T === $this->nodeTypeField->getType()) {
            return '
    /**
     * @return array CustomForm array
     */
    public function '.$this->nodeTypeField->getGetterName().'()
    {
        if (null === $this->' . $this->nodeTypeField->getName() . ') {
            $this->' . $this->nodeTypeField->getName() . ' = $this->getNode()->getHandler()->getCustomFormsFromFieldName("'.$this->nodeTypeField->getName().'");
        }
        return $this->' . $this->nodeTypeField->getName() . ';
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

        return 1;
    }

    /**
     * @return string
     */
    private function generateAlternativeGetter()
    {
        if ($this->nodeTypeField->getType() === NodeTypeField::YAML_T) {
            $assignation = '$this->'.$this->nodeTypeField->getName();
            return '
    /**
     * @return mixed
     */
    public function '.$this->nodeTypeField->getGetterName().'AsObject()
    {
        return \Symfony\Component\Yaml\Yaml::parse('.$assignation.');
    }'.PHP_EOL;
        }

        return '';
    }
}
