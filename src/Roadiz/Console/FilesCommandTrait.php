<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

trait FilesCommandTrait
{
    /**
     * @return string
     */
    protected function getPublicFolderName()
    {
        return '/exported_public';
    }

    /**
     * @return string
     */
    protected function getPrivateFolderName()
    {
        return '/exported_private';
    }

    /**
     * @return string
     */
    protected function getFontsFolderName()
    {
        return '/exported_fonts';
    }
}
