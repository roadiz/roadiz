<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Markdown\Generators;

use RZ\Roadiz\Core\Entities\NodeType;

final class ChildrenNodeFieldGenerator extends AbstractFieldGenerator
{
    public function getContents(): string
    {
        return implode("\n\n", [
            $this->getIntroduction(),
            '#### ' . $this->translator->trans('available_children_blocks'),
            $this->getAvailableChildren()
        ]);
    }

    /**
     * @return NodeType[]
     */
    private function getChildrenNodeTypes(): array
    {
        if (null !== $this->field->getDefaultValues()) {
            return array_map(function (string $nodeTypeName) {
                return $this->nodeTypesBag->get($nodeTypeName);
            }, explode(',', $this->field->getDefaultValues()));
        }
        return [];
    }

    private function getAvailableChildren(): string
    {
        return implode("\n", array_map(function (NodeType $nodeType) {
            return implode("\n\n", [
                '* ' . trim($nodeType->getDisplayName()),
            ]);
        }, $this->getChildrenNodeTypes())) . "\n";
    }
}
