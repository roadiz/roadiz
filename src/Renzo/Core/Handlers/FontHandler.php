<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file FontHandler.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Handlers;

use RZ\Renzo\Core\Entities\Font;
use RZ\Renzo\Core\Kernel;
use Symfony\Component\Finder\Finder;

/**
 * Handle operations with fonts entities..
 */
class FontHandler
{
    protected $font = null;

    /**
     * @param RZ\Renzo\Core\Entities\Font $font
     */
    public function __construct(Font $font)
    {
        $this->font = $font;
    }
    /**
     * @return RZ\Renzo\Core\Entities\Font Current font entity
     */
    public function getFont()
    {
        return $this->font;
    }

    /**
     * Generate a font download url.
     *
     * @param string $extension Select a specific font file.
     * @param string $token     Csrf token to protect from requesting font more than once.
     *
     * @return string
     */
    public function getDownloadUrl($extension, $token)
    {
        return Kernel::getService('urlGenerator')->generate(
            'FontFile',
            array(
                'filename'  => $this->font->getHash(),
                'extension' => $extension,
                'token'     => $token
            )
        );
    }
}
