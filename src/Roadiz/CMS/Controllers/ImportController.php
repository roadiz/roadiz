<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Controllers;

use RZ\Roadiz\Attribute\Importer\AttributeImporter;
use RZ\Roadiz\CMS\Importers\EntityImporterInterface;
use RZ\Roadiz\CMS\Importers\GroupsImporter;
use RZ\Roadiz\CMS\Importers\NodesImporter;
use RZ\Roadiz\CMS\Importers\NodeTypesImporter;
use RZ\Roadiz\CMS\Importers\RolesImporter;
use RZ\Roadiz\CMS\Importers\SettingsImporter;
use RZ\Roadiz\CMS\Importers\TagsImporter;
use RZ\Roadiz\Utils\Theme\ThemeResolverInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Themes\Install\InstallApp;

/**
 * Generic importer class for themes fixtures.
 */
class ImportController extends AppController
{
    protected function validateAccess(): void
    {
        if (!$this->get('kernel')->isInstallMode()) {
            throw $this->createAccessDeniedException('Import entry points are only available from install.');
        }
    }

    /**
     * @param string  $classImporter
     * @param Request $request
     * @param int|null $themeId
     *
     * @return Response
     * @throws \ReflectionException
     */
    protected function genericImportAction(string $classImporter, Request $request, ?int $themeId = null)
    {
        $this->validateAccess();

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
     * @param int|null    $themeId
     *
     * @return Response
     * @throws \ReflectionException
     */
    public function importSettingsAction(Request $request, ?int $themeId = null)
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
     * @param int|null     $themeId
     *
     * @return Response
     */
    public function importRolesAction(Request $request, ?int $themeId = null)
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
     * @param int|null     $themeId
     *
     * @return Response
     */
    public function importGroupsAction(Request $request, ?int $themeId = null)
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
     * @param int|null     $themeId
     *
     * @return Response
     */
    public function importNodeTypesAction(Request $request, ?int $themeId = null)
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
     * @param int|null     $themeId
     *
     * @return Response
     */
    public function importTagsAction(Request $request, ?int $themeId = null)
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
     * @param int|null $themeId
     *
     * @return Response
     */
    public function importAttributesAction(Request $request, ?int $themeId = null)
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
     * @param int|null $themeId
     *
     * @return Response
     */
    public function importNodesAction(Request $request, ?int $themeId = null)
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
     * @param int|null $themeId
     *
     * @return Response
     */
    protected function importContent(string $pathFile, string $classImporter, ?int $themeId)
    {
        $data = [];
        $data['status'] = false;
        /** @var ThemeResolverInterface $themeResolver */
        $themeResolver = $this->get('themeResolver');
        try {
            if (null === $themeId) {
                $path = $pathFile;
            } else {
                $theme = $themeResolver->findById($themeId);

                if ($theme === null) {
                    throw new BadRequestHttpException('Theme don’t exist in database.');
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
                $this->get('em')->flush();
            } else {
                throw new BadRequestHttpException('File: ' . $path . ' don’t exist');
            }
        } catch (\Exception $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
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
