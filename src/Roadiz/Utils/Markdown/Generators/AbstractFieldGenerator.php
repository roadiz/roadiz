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
 * @file AbstractFieldGenerator.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Utils\Markdown\Generators;

use RZ\Roadiz\Core\Bags\NodeTypes;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use Symfony\Component\Translation\Translator;

abstract class AbstractFieldGenerator
{
    /**
     * @var NodeTypeField
     */
    protected $field;
    /**
     * @var Translator
     */
    protected $translator;
    /**
     * @var NodeTypes
     */
    protected $nodeTypesBag;
    /**
     * @var MarkdownGeneratorFactory
     */
    protected $markdownGeneratorFactory;

    /**
     * AbstractFieldGenerator constructor.
     *
     * @param MarkdownGeneratorFactory $fieldGeneratorFactory
     * @param NodeTypeField            $field
     * @param NodeTypes                $nodeTypesBag
     * @param Translator               $translator
     */
    public function __construct(
        MarkdownGeneratorFactory $fieldGeneratorFactory,
        NodeTypeField $field,
        NodeTypes $nodeTypesBag,
        Translator $translator
    ) {
        $this->field = $field;
        $this->nodeTypesBag = $nodeTypesBag;
        $this->translator = $translator;
        $this->markdownGeneratorFactory = $fieldGeneratorFactory;
    }

    abstract public function getContents(): string;

    /**
     * @return string
     */
    public function getIntroduction(): string
    {
        $lines = [
            '### ' . $this->field->getLabel(),
        ];
        if (!empty($this->field->getDescription())) {
            $lines[] = $this->field->getDescription();
        }
        $lines = array_merge($lines, [
            '',
            '|     |     |',
            '| --- | --- |',
            '| **' . $this->translator->trans('docs.type') . '** | ' . $this->translator->trans(NodeTypeField::$typeToHuman[$this->field->getType()]) .' |',
            '| **' . $this->translator->trans('docs.technical_name') . '** | `' . $this->field->getVarName() . '` |',
            '| **' . $this->translator->trans('docs.universal') . '** | *' . $this->markdownGeneratorFactory->getHumanBool($this->field->isUniversal()) . '* |',
        ]);

        if (!empty($this->field->getGroupName())) {
            $lines[] = '| **' . $this->translator->trans('docs.group') . '** | ' . $this->field->getGroupName() . ' |';
        }
        if ($this->field->getExcludeFromSearch()) {
            $lines[] = '| **' . $this->translator->trans('docs.excluded_from_search') . '** | *' . $this->markdownGeneratorFactory->getHumanBool($this->field->getExcludeFromSearch()) . '* |';
        }
        if (!$this->field->isVisible()) {
            $lines[] = '| **' . $this->translator->trans('docs.visible') . '** | *' . $this->markdownGeneratorFactory->getHumanBool($this->field->isVisible()) . '* |';
        }

        return implode("\n", $lines) . "\n";
    }
}
