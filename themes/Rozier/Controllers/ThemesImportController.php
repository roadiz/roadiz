<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\CMS\Controllers\ImportController;

/**
 * @package Themes\Rozier\Controllers
 */
class ThemesImportController extends ImportController
{
    protected function validateAccess(): void
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_THEMES');
    }
}
