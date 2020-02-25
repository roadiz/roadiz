<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Markdown\Generators;

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
                '#### ' . trim($this->translator->trans($value)),
                '`' . $value . '`',
                ''
            ]);
        }, explode(',', $this->field->getDefaultValues())));
    }
}
