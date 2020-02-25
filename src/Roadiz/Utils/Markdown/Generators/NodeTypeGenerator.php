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
 * @file EntityGenerator.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Utils\Markdown\Generators;

use RZ\Roadiz\Core\AbstractEntities\AbstractField;
use RZ\Roadiz\Core\Bags\NodeTypes;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use Symfony\Component\Translation\Translator;

/**
 * Class EntityGenerator
 *
 * @package RZ\Roadiz\Utils\Markdown\Generators
 */
class NodeTypeGenerator
{
    /**
     * @var Translator
     */
    protected $translator;
    /**
     * @var NodeType
     */
    private $nodeType;

    /**
     * @var array
     */
    private $fieldGenerators;

    /**
     * @var NodeTypes
     */
    private $nodeTypesBag;

    /**
     * EntityGenerator constructor.
     *
     * @param NodeType   $nodeType
     * @param NodeTypes  $nodeTypesBag
     * @param Translator $translator
     */
    public function __construct(NodeType $nodeType, NodeTypes $nodeTypesBag, Translator $translator)
    {
        $this->nodeType = $nodeType;
        $this->nodeTypesBag = $nodeTypesBag;
        $this->fieldGenerators = [];
        $this->translator = $translator;

        /** @var NodeTypeField $field */
        foreach ($this->nodeType->getFields() as $field) {
            $this->fieldGenerators[] = $this->getFieldGenerator($field);
        }
    }

    /**
     * @param NodeTypeField $field
     * @return AbstractFieldGenerator
     */
    protected function getFieldGenerator(NodeTypeField $field): AbstractFieldGenerator
    {
        switch ($field->getType()) {
            case NodeTypeField::CHILDREN_T:
                return new ChildrenNodeFieldGenerator($field, $this->nodeTypesBag, $this->translator);
            case NodeTypeField::MULTIPLE_T:
            case NodeTypeField::ENUM_T:
                return new DefaultValuedFieldGenerator($field, $this->nodeTypesBag, $this->translator);
            default:
                return new CommonFieldGenerator($field, $this->nodeTypesBag, $this->translator);
        }
    }

    public function getMenuEntry(): string
    {
        return '['.$this->nodeType->getDisplayName().']('.$this->getPath().')';
    }

    public function getType(): string
    {
        return $this->nodeType->isReachable() ? 'page' : 'block';
    }

    public function getPath(): string
    {
        return $this->getType() . '/' . $this->nodeType->getName() . '.md';
    }

    public function getContents(): string
    {
        return implode("\n\n", [
            $this->getIntroduction(),
            '## ' . $this->translator->trans('fields'),
            $this->getFieldsContents()
        ]);
    }

    protected function getIntroduction(): string
    {
        return implode("\n", [
            '# ' . $this->nodeType->getDisplayName(),
            '',
            '`' . $this->nodeType->getName() . '` ' . $this->nodeType->getDescription(),
        ]);
    }

    protected function getFieldsContents(): string
    {
        return implode("\n", array_map(function (AbstractFieldGenerator $abstractFieldGenerator) {
            return $abstractFieldGenerator->getContents();
        }, $this->fieldGenerators));
    }
}
