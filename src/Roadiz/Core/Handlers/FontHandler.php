<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Handlers;

use RZ\Roadiz\Core\Entities\Font;

/**
 * Handle operations with fonts entities.
 */
class FontHandler extends AbstractHandler
{
    protected ?Font $font = null;

    /**
     * @return Font
     */
    public function getFont(): Font
    {
        if (null === $this->font) {
            throw new \BadMethodCallException('Font is null');
        }
        return $this->font;
    }

    /**
     * @param Font $font
     * @return FontHandler
     */
    public function setFont(Font $font)
    {
        $this->font = $font;
        return $this;
    }
}
