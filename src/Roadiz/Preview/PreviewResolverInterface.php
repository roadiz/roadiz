<?php
declare(strict_types=1);

namespace RZ\Roadiz\Preview;

interface PreviewResolverInterface
{
    public function isPreview(): bool;
}
