<?php
declare(strict_types=1);

namespace RZ\Roadiz\Documentation\Generators;

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
            '| **' . trim($this->translator->trans('docs.type')) . '** | ' . $this->translator->trans(NodeTypeField::$typeToHuman[$this->field->getType()]) .' |',
            '| **' . trim($this->translator->trans('docs.technical_name')) . '** | `' . $this->field->getVarName() . '` |',
            '| **' . trim($this->translator->trans('docs.universal')) . '** | *' . $this->markdownGeneratorFactory->getHumanBool($this->field->isUniversal()) . '* |',
        ]);

        if (!empty($this->field->getGroupName())) {
            $lines[] = '| **' . trim($this->translator->trans('docs.group')) . '** | ' . $this->field->getGroupName() . ' |';
        }
        if ($this->field->getExcludeFromSearch()) {
            $lines[] = '| **' . trim($this->translator->trans('docs.excluded_from_search')) . '** | *' . $this->markdownGeneratorFactory->getHumanBool($this->field->getExcludeFromSearch()) . '* |';
        }
        if (!$this->field->isVisible()) {
            $lines[] = '| **' . trim($this->translator->trans('docs.visible')) . '** | *' . $this->markdownGeneratorFactory->getHumanBool($this->field->isVisible()) . '* |';
        }

        return implode("\n", $lines) . "\n";
    }
}
