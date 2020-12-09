<?php
declare(strict_types=1);

namespace RZ\Roadiz\Documentation\Generators;

use RZ\Roadiz\Core\Entities\NodeType;

class ChildrenNodeFieldGenerator extends AbstractFieldGenerator
{
    public function getContents(): string
    {
        return implode("\n\n", [
            $this->getIntroduction(),
            '#### ' . $this->translator->trans('docs.available_children_blocks'),
            $this->getAvailableChildren()
        ]);
    }

    /**
     * @return NodeType[]
     */
    protected function getChildrenNodeTypes(): array
    {
        if (null !== $this->field->getDefaultValues()) {
            return array_filter(array_map(function (string $nodeTypeName) {
                return $this->nodeTypesBag->get(trim($nodeTypeName));
            }, explode(',', $this->field->getDefaultValues() ?? '')));
        }
        return [];
    }

    protected function getAvailableChildren(): string
    {
        return implode("\n", array_map(function (NodeType $nodeType) {
            $nodeTypeGenerator = $this->markdownGeneratorFactory->createForNodeType($nodeType);
            return implode("\n", [
                '* **' . trim($nodeTypeGenerator->getMenuEntry()) . '**    ',
                $nodeType->getDescription(),
            ]);
        }, $this->getChildrenNodeTypes())) . "\n";
    }
}
