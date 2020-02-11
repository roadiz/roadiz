<?php
declare(strict_types=1);
/**
 * Copyright (c) 2017. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file NonVirtualFieldGenerator.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Utils\Doctrine\Generators;

use RZ\Roadiz\Core\Entities\NodeTypeField;

/**
 * Class NonVirtualFieldGenerator.
 *
 *
 * @package RZ\Roadiz\Utils\Doctrine\Generators
 */
class NonVirtualFieldGenerator extends AbstractFieldGenerator
{
    /**
     * Generate PHP annotation block for Doctrine table indexes.
     *
     * @return string
     */
    public function getFieldIndex(): string
    {
        if ($this->field->isIndexed()) {
            return '@ORM\Index(columns={"'.$this->field->getName().'"})';
        }

        return '';
    }

    /**
     * @inheritDoc
     */
    public function getFieldAnnotation(): string
    {
        $serializationType = '';
        $exclusion = $this->excludeFromSerialization() ?
            '@Serializer\Exclude()' :
            '@Serializer\Groups({"nodes_sources", "nodes_sources_'.($this->field->getGroupNameCanonical() ?: 'default').'"})';
        $ormParams = [
            'type' => '"' . NodeTypeField::$typeToDoctrine[$this->field->getType()] . '"',
            'nullable' => 'true',
            'name' => '"' . $this->field->getName() . '"',
        ];

        if ($this->field->getType() == NodeTypeField::DECIMAL_T) {
            $ormParams['precision'] = 18;
            $ormParams['scale'] = 3;
            $serializationType = '@Serializer\Type("double")';
        } elseif ($this->field->getType() == NodeTypeField::BOOLEAN_T) {
            $ormParams['nullable'] = 'false';
            $ormParams['options'] = '{"default" = false}';
            $serializationType = '@Serializer\Type("boolean")';
        } elseif ($this->field->getType() == NodeTypeField::INTEGER_T) {
            $serializationType = '@Serializer\Type("integer")';
        }

        return '
    /**
     * ' . $this->field->getLabel() .'
     *
     * @Gedmo\Versioned
     * @ORM\Column(' . static::flattenORMParameters($ormParams) . ')
     * ' . $exclusion . '
     * ' . $serializationType . '
     */'.PHP_EOL;
    }

    /**
     * @inheritDoc
     */
    public function getFieldDeclaration(): string
    {
        if ($this->field->getType() === NodeTypeField::BOOLEAN_T) {
            return '    private $'.$this->field->getVarName().' = false;'.PHP_EOL;
        } elseif ($this->field->getType() === NodeTypeField::INTEGER_T) {
            return '    private $'.$this->field->getVarName().' = 0;'.PHP_EOL;
        } else {
            return '    private $'.$this->field->getVarName().';'.PHP_EOL;
        }
    }

    /**
     * @inheritDoc
     */
    public function getFieldGetter(): string
    {
        $assignation = '$this->'.$this->field->getVarName();

        return '
    /**
     * @return mixed
     */
    public function '.$this->field->getGetterName().'()
    {
        return '.$assignation.';
    }'.PHP_EOL;
    }

    /**
     * @inheritDoc
     */
    public function getFieldSetter(): string
    {
        $assignation = '$'.$this->field->getVarName();

        if ($this->field->getType() === NodeTypeField::BOOLEAN_T) {
            $assignation = '(boolean) $'.$this->field->getVarName();
        }
        if ($this->field->getType() === NodeTypeField::INTEGER_T) {
            $assignation = '(int) $'.$this->field->getVarName();
        }
        if ($this->field->getType() === NodeTypeField::DECIMAL_T) {
            $assignation = '(double) $'.$this->field->getVarName();
        }

        return '
    /**
     * @param mixed $'.$this->field->getVarName().'
     *
     * @return $this
     */
    public function '.$this->field->getSetterName().'($'.$this->field->getVarName().')
    {
        $this->'.$this->field->getVarName().' = '.$assignation.';

        return $this;
    }'.PHP_EOL;
    }
}
