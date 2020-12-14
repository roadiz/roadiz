<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Handlers;

use RZ\Roadiz\Core\Entities\Font;

/**
 * Handle operations with fonts entities.
 */
class FontHandler extends AbstractHandler
{
    /**
     * @var Font
     */
    protected $font;

    /**
     * @return Font
     */
    public function getFont()
    {
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
