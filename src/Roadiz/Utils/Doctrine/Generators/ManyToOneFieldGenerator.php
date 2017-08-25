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
 * @file ManyToOneFieldGenerator.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Utils\Doctrine\Generators;

use Symfony\Component\Yaml\Yaml;

/**
 * Class ManyToOneFieldGenerator
 * @package RZ\Roadiz\Utils\Doctrine\Generators
 */
class ManyToOneFieldGenerator extends AbstractFieldGenerator
{
    /**
     * @inheritDoc
     */
    public function getFieldAnnotation()
    {
        /*
         *
         * Many Users have One Address.
         * @ORM\ManyToOne(targetEntity="Address")
         * @ORM\JoinColumn(name="address_id", referencedColumnName="id", onDelete="SET NULL")
         *
         */
        $configuration = Yaml::parse($this->field->getDefaultValues());
        $ormParams = [
            'name' => '"' . $this->field->getName() . '_id"',
            'referencedColumnName' => '"id"',
            'onDelete' => '"SET NULL"',
        ];
        return '
    /**
     * ' . $this->field->getLabel() .'
     * @var \\' . $configuration['classname'] . '
     * @ORM\ManyToOne(targetEntity="'. $configuration['classname'] .'")
     * @ORM\JoinColumn(' . static::flattenORMParameters($ormParams) . ')
     */'.PHP_EOL;
    }

    /**
     * @inheritDoc
     */
    public function getFieldGetter()
    {
        return '
    /**
     * @return \RZ\Roadiz\Core\AbstractEntities\AbstractEntity
     */
    public function '.$this->field->getGetterName().'()
    {
        return $this->' . $this->field->getName() . ';
    }'.PHP_EOL;
    }

    /**
     * @inheritDoc
     */
    public function getFieldSetter()
    {
        return '
    /**
     * @var $'.$this->field->getName().'
     * @return $this
     */
    public function '.$this->field->getSetterName().'($'.$this->field->getName().' = null)
    {
        $this->'.$this->field->getName().' = $'.$this->field->getName().';
        
        return $this;
    }'.PHP_EOL;
    }
}
