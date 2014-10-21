<?php
/*
 * Copyright REZO ZERO 2014
 *
 * Description
 *
 * @file ImportController.php
 * @copyright REZO ZERO 2014
 * @author Maxime Constantinian
 */

namespace RZ\Renzo\CMS\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\CMS\Controllers\AppController;
use RZ\Renzo\CMS\Importer\SettingsImporter;
use RZ\Renzo\Core\Entities\Document;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Entities\User;
use RZ\Renzo\Core\Entities\Role;

use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\Validation;

use GeneratedNodeSources\NSPage;

use Themes\Install\InstallApp;

/**
 * Class to have generique importer for all theme.
 */
class ImportController extends InstallApp
{
    /**
     * Import theme's Settings file.
     *
     * @param int $themeId
     *
     * @return string
     */
    public static function importSettingsAction(Request $request, $filename, $themeId = null)
    {
        #$pathFile = '/Resources/import/settings.rzt';
        $classImporter = "RZ\Renzo\CMS\Importers\SettingsImporter";
        return self::importContent($filename, $classImporter, $themeId);
    }

    /**
     * Import theme's Roles file.
     *
     * @param int $themeId
     *
     * @return string
     */
    public static function importRolesAction(Request $request, $filename, $themeId = null)
    {
        #$pathFile = '/Resources/import/roles.rzt';
        $classImporter = "RZ\Renzo\CMS\Importers\RolesImporter";
        return self::importContent($filename, $classImporter, $themeId);
    }

    /**
     * Import theme's Groups file.
     *
     * @param int $themeId
     *
     * @return string
     */
    public static function importGroupsAction(Request $request, $filename, $themeId = null)
    {
        #$pathFile = '/Resources/import/groups.rzt';
        $classImporter = "RZ\Renzo\CMS\Importers\GroupsImporter";
        return self::importContent($filename, $classImporter, $themeId);
    }

    /**
     * Import NodeTypes file.
     *
     * @param int $themeId
     *
     * @return string
     */
    public static function importNodeTypesAction(Request $request, $filename, $themeId = null)
    {
        #$pathFile = '/Resources/import/nodetype/' . basename($filename) . '.rzt';
        $classImporter = "RZ\Renzo\CMS\Importers\NodeTypesImporter";
        return self::importContent($filename, $classImporter, $themeId);
    }

    /**
     * Import Tags file.
     *
     * @param int $themeId
     *
     * @return string
     */
    public static function importTagsAction(Request $request, $filename, $themeId = null)
    {
        #$pathFile = '/Resources/import/nodetype/' . basename($filename) . '.rzt';
        $classImporter = "RZ\Renzo\CMS\Importers\TagsImporter";
        return self::importContent($filename, $classImporter, $themeId);
    }

    /**
     * Import Nodes file.
     *
     *
     * @return string
     */
    public static function importNodesAction(Request $request, $filename, $themeId = null)
    {
        $classImporter = "RZ\Renzo\CMS\Importers\NodesImporter";
        return self::importContent($filename, $classImporter, $themeId);
    }

    /**
     * Import theme's Settings file.
     *
     * @param string $pathFile
     * @param string $classImporter
     * @param int    $themeId
     *
     * @return string
     */
    public static function importContent($pathFile, $classImporter, $themeId)
    {
        $data = array();
        $data['status'] = false;
        try {
            if (null === $themeId) {
                $path = RENZO_ROOT . '/themes/Install/' . $pathFile;
            } else {
                $theme = Kernel::getService('em')
                         ->find('RZ\Renzo\Core\Entities\Theme', $themeId);

                if ($theme === null) {
                    throw new \Exception('Theme don\'t exist in database.');
                }

                $dir = explode('\\', $theme->getClassName());
                $path = RENZO_ROOT . "/themes/" . $dir[2] . '/' . $pathFile;

            }
            if (file_exists($path)) {
                $file = file_get_contents($path);
                $ret = $classImporter::importJsonFile($file);
            } else {
                throw new \Exception('File: ' . $path . ' don\'t exist');
            }
        } catch (\Exception $e) {
            $data['error'] = $e->getMessage();
            return new Response(
                json_encode($data),
                Response::HTTP_NOT_FOUND,
                array('content-type' => 'application/javascript')
            );
        }
        $data['status'] = true;
        if ($classImporter == "RZ\Renzo\CMS\Importers\NodeTypesImporter") {
            $data['request'] = Kernel::getService('urlGenerator')->generate('installUpdateSchema');
        }
        return new Response(
            json_encode($data),
            Response::HTTP_OK,
            array('content-type' => 'application/javascript')
        );
    }
}
