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

use Themes\Install\InstallApp;

/**
 * Class to have generiaue importer for all theme.
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
    static function importSettingsAction($themeId = null)
    {
        $pathFile = '/Resources/import/settings.rzt';
        $classImporter = "RZ\Renzo\CMS\Importers\SettingsImporter";
        return self::importContent($pathFile, $classImporter, $themeId);
    }

    /**
     * Import theme's Roles file.
     *
     * @param int $themeId
     *
     * @return string
     */
    static function importRolesAction($themeId = null)
    {
        $pathFile = '/Resources/import/roles.rzt';
        $classImporter = "RZ\Renzo\CMS\Importers\RolesImporter";
        return self::importContent($pathFile, $classImporter, $themeId);
    }

    /**
     * Import theme's Groups file.
     *
     * @param int $themeId
     *
     * @return string
     */
    static function importGroupsAction($themeId = null)
    {
        $pathFile = '/Resources/import/groups.rzt';
        $classImporter = "RZ\Renzo\CMS\Importers\GroupsImporter";
        return self::importContent($pathFile, $classImporter, $themeId);
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
    static function importContent($pathFile, $classImporter, $themeId)
    {
        $data = array();
        $data['status'] = false;
        try {
            if (null === $themeId) {
                $path = RENZO_ROOT . '/themes/install' . $pathFile;
            } else {
                $theme = Kernel::getInstance()->em()
                         ->find('RZ\Renzo\Core\Entities\Theme', $themeId);
                $dir = dir($theme->getClassName());
                if ($theme === null) {
                    throw new \Exception('Theme don\'t exist in database.');
                }
                $path = RENZO_ROOT . '/themes/' . $dir . $pathFile;
            }
            if (file_exists($path)) {
                $file = file_get_contents($path);
                $ret = $classImporter::importJsonFile($file);
            } else {
                throw new \Exception('File: ' . $path . ' don\'t existe');
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
        return new Response(
            json_encode($data),
            Response::HTTP_OK,
            array('content-type' => 'application/javascript')
        );
    }

}