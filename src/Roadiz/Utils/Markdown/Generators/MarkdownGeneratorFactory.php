<?php
/**
 * Copyright (c) 2020. Ambroise Maupate and Julien Blanchet
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
 * @file FieldGeneratorFactory.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Markdown\Generators;

use RZ\Roadiz\Core\Bags\NodeTypes;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use Symfony\Component\Translation\Translator;

final class MarkdownGeneratorFactory
{
    /**
     * @var NodeTypes
     */
    private $nodeTypesBag;
    /**
     * @var Translator
     */
    private $translator;

    /**
     * FieldGeneratorFactory constructor.
     *
     * @param NodeTypes  $nodeTypesBag
     * @param Translator $translator
     */
    public function __construct(NodeTypes $nodeTypesBag, Translator $translator)
    {
        $this->nodeTypesBag = $nodeTypesBag;
        $this->translator = $translator;
    }

    public function getHumanBool(bool $bool): string
    {
        return $bool ? $this->translator->trans('yes') : $this->translator->trans('no');
    }

    /**
     * @param NodeType $nodeType
     *
     * @return NodeTypeGenerator
     */
    public function createForNodeType(NodeType $nodeType): NodeTypeGenerator
    {
        return new NodeTypeGenerator(
            $nodeType,
            $this->nodeTypesBag,
            $this->translator,
            $this
        );
    }

    /**
     * @param NodeTypeField $field
     *
     * @return AbstractFieldGenerator
     */
    public function createForNodeTypeField(NodeTypeField $field): AbstractFieldGenerator
    {
        switch ($field->getType()) {
            case NodeTypeField::CHILDREN_T:
                return new ChildrenNodeFieldGenerator($this, $field, $this->nodeTypesBag, $this->translator);
            case NodeTypeField::MULTIPLE_T:
            case NodeTypeField::ENUM_T:
                return new DefaultValuedFieldGenerator($this, $field, $this->nodeTypesBag, $this->translator);
            default:
                return new CommonFieldGenerator($this, $field, $this->nodeTypesBag, $this->translator);
        }
    }
}
