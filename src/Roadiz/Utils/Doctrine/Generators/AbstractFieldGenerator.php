<?php
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
 * @file AbstractFieldGenerator.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Utils\Doctrine\Generators;

use RZ\Roadiz\Core\Entities\NodeTypeField;

abstract class AbstractFieldGenerator
{
    /**
     * @var NodeTypeField
     */
    protected $field;

    /**
     * AbstractFieldGenerator constructor.
     * @param NodeTypeField $field
     */
    public function __construct(NodeTypeField $field)
    {
        $this->field = $field;
    }

    /**
     * @param array $ormParams
     *
     * @return string
     */
    public static function flattenORMParameters(array $ormParams): string
    {
        $flatParams = [];
        foreach ($ormParams as $key => $value) {
            $flatParams[] = $key . '=' . $value;
        }

        return implode(', ', $flatParams);
    }

    /**
     * Generate PHP code for current doctrine field.
     *
     * @return string
     */
    public function getField(): string
    {
        return $this->getFieldAnnotation().
            $this->getFieldDeclaration().
            $this->getFieldGetter().
            $this->getFieldAlternativeGetter().
            $this->getFieldSetter().PHP_EOL;
    }

    /**
     * @return string
     */
    protected function getFieldAnnotation(): string
    {
        return '
    /**
     * ' . $this->field->getLabel() .'
     * (Virtual field, this var is a buffer)
     * @Serializer\Exclude
     */'.PHP_EOL;
    }

    /**
     * Generate PHP property declaration block.
     */
    protected function getFieldDeclaration(): string
    {
        /*
         * Buffer var to get referenced entities (documents, nodes, cforms, doctrine entities)
         */
        return '    private $'.$this->field->getName().';'.PHP_EOL;
    }

    /**
     * Generate PHP alternative getter method block.
     *
     * @return string
     */
    abstract protected function getFieldGetter(): string;

    /**
     * Generate PHP alternative getter method block.
     *
     * @return string
     */
    protected function getFieldAlternativeGetter(): string
    {
        return '';
    }

    /**
     * Generate PHP setter method block.
     *
     * @return string
     */
    protected function getFieldSetter(): string
    {
        return '';
    }

    /**
     * Generate PHP annotation block for Doctrine table indexes.
     *
     * @return string
     */
    public function getFieldIndex(): string
    {
        return '';
    }

    /**
     * Generate PHP property initialization for class constructor.
     *
     * @return string
     */
    public function getFieldConstructorInitialization(): string
    {
        return '';
    }

    /**
     * @return bool
     */
    protected function excludeFromSerialization()
    {
        return false;
    }
}
