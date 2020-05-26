<?php
declare(strict_types=1);

namespace RZ\Roadiz\Documentation\Generators;

final class NodeReferencesFieldGenerator extends ChildrenNodeFieldGenerator
{
    public function getContents(): string
    {
        return implode("\n\n", [
            $this->getIntroduction(),
            '#### ' . $this->translator->trans('docs.available_referenced_nodes'),
            $this->getAvailableChildren()
        ]);
    }
}
