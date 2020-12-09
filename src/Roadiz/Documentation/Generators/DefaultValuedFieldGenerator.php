<?php
declare(strict_types=1);

namespace RZ\Roadiz\Documentation\Generators;

final class DefaultValuedFieldGenerator extends AbstractFieldGenerator
{
    public function getContents(): string
    {
        return implode("\n\n", [
            $this->getIntroduction(),
            $this->getDefaultValues()
        ]);
    }

    private function getDefaultValues(): string
    {
        return implode("\n", array_map(function (string $value) {
            return implode("\n", [
                '* **' . trim($this->translator->trans(trim($value))) . '** `' . $value . '`',
            ]);
        }, explode(',', $this->field->getDefaultValues() ?? ''))) . "\n";
    }
}
