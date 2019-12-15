<?php
declare(strict_types=1);
/**
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
 * @file ImportController.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\CMS\Controllers;

use RZ\Roadiz\Attribute\Importer\AttributeImporter;
use RZ\Roadiz\CMS\Importers\EntityImporterInterface;
use RZ\Roadiz\CMS\Importers\GroupsImporter;
use RZ\Roadiz\CMS\Importers\NodesImporter;
use RZ\Roadiz\CMS\Importers\NodeTypesImporter;
use RZ\Roadiz\CMS\Importers\RolesImporter;
use RZ\Roadiz\CMS\Importers\SettingsImporter;
use RZ\Roadiz\CMS\Importers\TagsImporter;
use RZ\Roadiz\Core\Entities\Theme;
use RZ\Roadiz\Utils\Theme\ThemeResolverInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Themes\Install\InstallApp;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Generic importer class for themes fixtures.
 */
class ImportController extends AppController
{
    /**
     * @param  string  $classImporter
     * @param  Request $request
     * @param  integer $themeId
     *
     * @return Response
     */
    protected function genericImportAction($classImporter, Request $request, $themeId = null)
    {
        if (null !== $filename = $this->getFilename($request)) {
            if (null === $themeId) {
                $filename =  InstallApp::getThemeFolder() . '/' . $filename;
            }

            return $this->importContent($filename, $classImporter, $themeId);
        }

        throw new ResourceNotFoundException("No file to import found.");
    }

    /**
     * Get filename to import from POST request.
     *
     * @param  Request $request
     *
     * @return string|null
     */
    protected function getFilename(Request $request)
    {
        if ($request->getMethod() == Request::METHOD_POST &&
            $request->request->get("filename") != "") {
            return $request->request->get("filename");
        } else {
            return null;
        }
    }

    /**
     * Import theme's Settings file.
     *
     * @param Request $request
     * @param int     $themeId
     *
     * @return Response
     */
    public function importSettingsAction(Request $request, $themeId = null)
    {
        return $this->genericImportAction(
            SettingsImporter::class,
            $request,
            $themeId
        );
    }

    /**
     * Import theme's Roles file.
     *
     * @param Request $request
     * @param int     $themeId
     *
     * @return Response
     */
    public function importRolesAction(Request $request, $themeId = null)
    {
        return $this->genericImportAction(
            RolesImporter::class,
            $request,
            $themeId
        );
    }

    /**
     * Import theme's Groups file.
     *
     * @param Request $request
     * @param int     $themeId
     *
     * @return Response
     */
    public function importGroupsAction(Request $request, $themeId = null)
    {
        return $this->genericImportAction(
            GroupsImporter::class,
            $request,
            $themeId
        );
    }

    /**
     * Import NodeTypes file.
     *
     * @param Request $request
     * @param int     $themeId
     *
     * @return Response
     */
    public function importNodeTypesAction(Request $request, $themeId = null)
    {
        return $this->genericImportAction(
            NodeTypesImporter::class,
            $request,
            $themeId
        );
    }

    /**
     * Import Tags file.
     *
     * @param Request $request
     * @param int     $themeId
     *
     * @return Response
     */
    public function importTagsAction(Request $request, $themeId = null)
    {
        return $this->genericImportAction(
            TagsImporter::class,
            $request,
            $themeId
        );
    }

    /**
     * Import Attributes file.
     *
     * @param Request $request
     * @param int     $themeId
     *
     * @return Response
     */
    public function importAttributesAction(Request $request, $themeId = null)
    {
        return $this->genericImportAction(
            AttributeImporter::class,
            $request,
            $themeId
        );
    }

    /**
     * Import Nodes file.
     *
     * @param Request $request
     * @param int     $themeId
     *
     * @return Response
     */
    public function importNodesAction(Request $request, $themeId = null)
    {
        return $this->genericImportAction(
            NodesImporter::class,
            $request,
            $themeId
        );
    }

    /**
     * Import theme's Settings file.
     *
     * @param string $pathFile
     * @param string $classImporter
     * @param int    $themeId
     *
     * @return Response
     */
    public function importContent($pathFile, $classImporter, $themeId)
    {
        $data = [];
        $data['status'] = false;
        /** @var ThemeResolverInterface $themeResolver */
        $themeResolver = $this->get('themeResolver');
        try {
            if (null === $themeId) {
                $path = $pathFile;
            } else {
                /** @var Theme $theme */
                $theme = $themeResolver->findById($themeId);

                if ($theme === null) {
                    throw new \Exception('Theme don’t exist in database.');
                }

                $classname = $theme->getClassName();
                $themeFolder = call_user_func([$classname, 'getThemeFolder']);
                $path = $themeFolder . '/' . $pathFile;
            }
            if (file_exists($path)) {
                $file = file_get_contents($path);
                /** @var EntityImporterInterface $importer */
                $importer = $this->get($classImporter);
                $importer->import($file);
            } else {
                throw new \Exception('File: ' . $path . ' don’t exist');
            }
        } catch (\Exception $e) {
            $data['error'] = $e->getMessage();
            return new JsonResponse(
                $data,
                Response::HTTP_NOT_FOUND
            );
        }
        $data['status'] = true;
        return new JsonResponse(
            $data,
            Response::HTTP_OK
        );
    }

    /**
     * @param string $message
     *
     * @return Response
     */
    public function throw404($message = '')
    {
        $data = [];
        $data['status'] = false;
        $data['error'] = 'File to import not found.';

        return new JsonResponse(
            $data,
            Response::HTTP_NOT_FOUND
        );
    }
}
