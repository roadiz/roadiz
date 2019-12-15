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
 * @file NodesFieldGenerator.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Utils\Doctrine\Generators;

use RZ\Roadiz\Core\Bags\NodeTypes;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;

/**
 * Class NodesFieldGenerator
 *
 * @package RZ\Roadiz\Utils\Doctrine\Generators
 */
class NodesFieldGenerator extends AbstractFieldGenerator
{
    /**
     * @var NodeTypes
     */
    private $nodeTypesBag;

    /**
     * NodesFieldGenerator constructor.
     *
     * @param NodeTypeField $field
     * @param NodeTypes     $nodeTypesBag
     */
    public function __construct(NodeTypeField $field, NodeTypes $nodeTypesBag)
    {
        parent::__construct($field);
        $this->nodeTypesBag = $nodeTypesBag;
    }

    /**
     * @return string
     */
    protected function getFieldSourcesName(): string
    {
        return $this->field->getName().'_sources';
    }
    /**
     * @return bool
     */
    protected function hasOnlyOneNodeType()
    {
        return count(explode(',', $this->field->getDefaultValues())) === 1;
    }

    /**
     * @return string
     */
    protected function getRepositoryClass(): string
    {
        if ($this->hasOnlyOneNodeType() === true) {
            $nodeTypeName = trim(explode(',', $this->field->getDefaultValues())[0]);

            /** @var NodeType $nodeType */
            $nodeType = $this->nodeTypesBag->get($nodeTypeName);
            if (null !== $nodeType) {
                return $nodeType->getSourceEntityFullQualifiedClassName();
            }
        }
        return NodesSources::class;
    }

    /**
     * @inheritDoc
     */
    public function getFieldGetter(): string
    {
        return '
    /**
     * @return Node[] '.$this->field->getName().' array
     * @deprecated Use '.$this->field->getGetterName().'Sources() instead to directly handle node-sources
     * @Serializer\Exclude
     */
    public function '.$this->field->getGetterName().'()
    {
        trigger_error(
            \'Method \' . __METHOD__ . \' is deprecated and will be removed in Roadiz v1.4. Use '.$this->field->getGetterName().'Sources instead to deal with NodesSources.\',
            E_USER_DEPRECATED
        );

        if (null === $this->' . $this->field->getName() . ') {
            if (null !== $this->objectManager) {
                 $this->' . $this->field->getName() . ' = $this->objectManager
                      ->getRepository(Node::class)
                      ->findByNodeAndFieldAndTranslation(
                          $this->getNode(),
                          $this->getNode()->getNodeType()->getFieldByName("'.$this->field->getName().'"),
                          $this->getTranslation()
                      );
            } else {
                $this->' . $this->field->getName() . ' = [];
            }
        }
        return $this->' . $this->field->getName() . ';
    }
    /**
     * ' . $this->getFieldSourcesName() .' NodesSources direct field buffer.
     * (Virtual field, this var is a buffer)
     * @Serializer\Exclude
     * @var NodesSources[]|null
     */
    private $'.$this->getFieldSourcesName().';

    /**
     * @return NodesSources[] '.$this->field->getName().' nodes-sources array
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"nodes_sources"})
     * @Serializer\SerializedName("'.$this->field->getName().'")
     */
    public function '.$this->field->getGetterName().'Sources()
    {
        if (null === $this->' . $this->getFieldSourcesName() . ') {
            if (null !== $this->objectManager) {
                 $this->' . $this->getFieldSourcesName() . ' = $this->objectManager
                      ->getRepository(\\'. $this->getRepositoryClass() .'::class)
                      ->findByNodesSourcesAndFieldAndTranslation(
                          $this,
                          $this->getNode()->getNodeType()->getFieldByName("'.$this->field->getName().'")
                      );
            } else {
                $this->' . $this->getFieldSourcesName() . ' = [];
            }
        }
        return $this->' . $this->getFieldSourcesName() . ';
    }'.PHP_EOL;
    }
}
