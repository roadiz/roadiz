<?php
declare(strict_types=1);

namespace RZ\Roadiz\Preview;

interface PreviewAwareInterface
{
    /**
     * @return bool
     */
    public function isPreview(): bool;

    /**
     * @param bool $preview
     * @return self
     */
    public function setPreview(bool $preview);
}
