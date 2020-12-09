<?php
declare(strict_types=1);

namespace RZ\Roadiz\Documentation\Generators;

use RZ\Roadiz\Core\Bags\NodeTypes;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use Symfony\Component\Translation\Translator;

/**
 * Class EntityGenerator
 *
 * @package RZ\Roadiz\Documentation\Generators
 */
class NodeTypeGenerator
{
    /**
     * @var Translator
     */
    protected $translator;
    /**
     * @var MarkdownGeneratorFactory
     */
    protected $markdownGeneratorFactory;
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
     * @param NodeType                 $nodeType
     * @param NodeTypes                $nodeTypesBag
     * @param Translator               $translator
     * @param MarkdownGeneratorFactory $markdownGeneratorFactory
     */
    public function __construct(
        NodeType $nodeType,
        NodeTypes $nodeTypesBag,
        Translator $translator,
        MarkdownGeneratorFactory $markdownGeneratorFactory
    ) {
        $this->nodeType = $nodeType;
        $this->nodeTypesBag = $nodeTypesBag;
        $this->fieldGenerators = [];
        $this->translator = $translator;
        $this->markdownGeneratorFactory = $markdownGeneratorFactory;

        /** @var NodeTypeField $field */
        foreach ($this->nodeType->getFields() as $field) {
            $this->fieldGenerators[] = $this->markdownGeneratorFactory->createForNodeTypeField($field);
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
            '## ' . $this->translator->trans('docs.fields'),
            $this->getFieldsContents()
        ]);
    }

    protected function getIntroduction(): string
    {
        $lines = [
            '# ' . $this->nodeType->getDisplayName(),
        ];
        if (!empty($this->nodeType->getDescription())) {
            $lines[] = $this->nodeType->getDescription();
        }
        $lines = array_merge($lines, [
            '',
            '|     |     |',
            '| --- | --- |',
            '| **' . trim($this->translator->trans('docs.technical_name')) . '** | `' . $this->nodeType->getName() . '` |',
        ]);

        if ($this->nodeType->isPublishable()) {
            $lines[] = '| **' . trim($this->translator->trans('docs.publishable')) . '** | *' . $this->markdownGeneratorFactory->getHumanBool($this->nodeType->isPublishable()) . '* |';
        }
        if (!$this->nodeType->isVisible()) {
            $lines[] = '| **' . trim($this->translator->trans('docs.visible') ). '** | *' . $this->markdownGeneratorFactory->getHumanBool($this->nodeType->isVisible()) . '* |';
        }

        return implode("\n", $lines);
    }

    protected function getFieldsContents(): string
    {
        return implode("\n", array_map(function (AbstractFieldGenerator $abstractFieldGenerator) {
            return $abstractFieldGenerator->getContents();
        }, $this->fieldGenerators));
    }
}
