<?php
/*
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 *
 * @file ThemesImportController.php
 * @author Maxime Constantinian
 */
namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Utils\Installer\ThemeInstaller;
use RZ\Roadiz\Core\Entities\Theme;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use RZ\Roadiz\Core\Exceptions\EntityRequiredException;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Type;
use RZ\Roadiz\CMS\Controllers\ImportController;

/**
 * {@inheritdoc}
 */
class ThemesImportController extends ImportController
{
    /**
     * Import theme's Settings file.
     *
     * @param int $themeId
     *
     * @return string
     */
    public function importSettingsAction(Request $request, $themeId = null)
    {
        $this->validateAccessForRole('ROLE_ACCESS_THEMES');

        return parent::importSettingsAction($request, $themeId);
    }

    /**
     * Import theme's Roles file.
     *
     * @param int $themeId
     *
     * @return string
     */
    public function importRolesAction(Request $request, $themeId = null)
    {
        $this->validateAccessForRole('ROLE_ACCESS_THEMES');

        return parent::importRolesAction($request, $themeId);
    }

    /**
     * Import theme's Groups file.
     *
     * @param int $themeId
     *
     * @return string
     */
    public function importGroupsAction(Request $request, $themeId = null)
    {
        $this->validateAccessForRole('ROLE_ACCESS_THEMES');

        return parent::importGroupsAction($request, $themeId);
    }

    /**
     * Import NodeTypes file.
     *
     * @param int $themeId
     *
     * @return string
     */
    public function importNodeTypesAction(Request $request, $themeId = null)
    {
        $this->validateAccessForRole('ROLE_ACCESS_THEMES');

        return parent::importNodeTypesAction($request, $themeId);
    }

    /**
     * Import Tags file.
     *
     * @param int $themeId
     *
     * @return string
     */
    public function importTagsAction(Request $request, $themeId = null)
    {
        $this->validateAccessForRole('ROLE_ACCESS_THEMES');

        return parent::importTagsAction($request, $themeId);
    }

    /**
     * Import Nodes file.
     *
     *
     * @return string
     */
    public function importNodesAction(Request $request, $themeId = null)
    {
        $this->validateAccessForRole('ROLE_ACCESS_THEMES');

        return parent::importNodesAction($request, $themeId);
    }
}
