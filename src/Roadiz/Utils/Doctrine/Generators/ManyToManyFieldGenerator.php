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
 * @file ManyToManyFieldGenerator.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Utils\Doctrine\Generators;

use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ManyToManyFieldGenerator
 * @package RZ\Roadiz\Utils\Doctrine\Generators
 */
class ManyToManyFieldGenerator extends AbstractFieldGenerator
{
    /**
     * @inheritDoc
     */
    public function getFieldAnnotation()
    {
        /*
         * Many Users have Many Groups.
         * @ManyToMany(targetEntity="Group")
         * @JoinTable(name="users_groups",
         *      joinColumns={@JoinColumn(name="user_id", referencedColumnName="id")},
         *      inverseJoinColumns={@JoinColumn(name="group_id", referencedColumnName="id")}
         */
        $entityA = StringHandler::variablize($this->field->getNodeType()->getName());
        $entityB = $this->field->getName();
        $configuration = Yaml::parse($this->field->getDefaultValues());
        $joinColumnParams = [
            'name' => '"'.$entityA.'_id"',
            'referencedColumnName' => '"id"'
        ];
        $inverseJoinColumns = [
            'name' => '"'.$entityB.'_id"',
            'referencedColumnName' => '"id"'
        ];
        $ormParams = [
            'name' => '"'. $entityA .'_' . $entityB . '"',
            'joinColumns' => '{@ORM\JoinColumn(' . static::flattenORMParameters($joinColumnParams) . ')}',
            'inverseJoinColumns' => '{@ORM\JoinColumn(' . static::flattenORMParameters($inverseJoinColumns) . ')}',
        ];
        return '
    /**
     * ' . $this->field->getLabel() .'
     * 
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="'. $configuration['classname'] .'")
     * @ORM\JoinTable(' . static::flattenORMParameters($ormParams) . ')
     */'.PHP_EOL;
    }

    /**
     * @inheritDoc
     */
    public function getFieldGetter()
    {
        return '
    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
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
     * @var \Doctrine\Common\Collections\ArrayCollection $'.$this->field->getName().'
     * @return $this
     */
    public function '.$this->field->getSetterName().'($'.$this->field->getName().' = null)
    {
        $this->'.$this->field->getName().' = $'.$this->field->getName().';
        
        return $this;
    }'.PHP_EOL;
    }

    /**
     * @inheritDoc
     */
    public function getFieldConstructorInitialization()
    {
        return '$this->' . $this->field->getName() . ' = new \Doctrine\Common\Collections\ArrayCollection();';
    }
}
