<?php
declare(strict_types=1);

namespace RZ\Roadiz\Documentation\Generators;

final class CommonFieldGenerator extends AbstractFieldGenerator
{
    public function getContents(): string
    {
        return implode("\n\n", [
            $this->getIntroduction()
        ]);
    }
}
