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
    public static function importSettingsAction(Request $request, $filename,  $themeId = null)
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
    public static function importRolesAction(Request $request, $filename, $themeId = null)
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
    public static function importGroupsAction(Request $request, $filename, $themeId = null)
    {
        $pathFile = '/Resources/import/groups.rzt';
        $classImporter = "RZ\Renzo\CMS\Importers\GroupsImporter";
        return self::importContent($pathFile, $classImporter, $themeId);
    }

    /**
     * Import NodeType's Groups file.
     *
     * @param int $themeId
     *
     * @return string
     */
    public static function importNodeTypesAction(Request $request, $filename, $themeId = null)
    {
        $pathFile = '/Resources/import/nodetype/' . basename($filename) . '.rzt';
        $classImporter = "RZ\Renzo\CMS\Importers\NodeTypesImporter";
        return self::importContent($pathFile, $classImporter, $themeId);
    }

    public static function createNode($array)
    {
        $nodeType = Kernel::getService('em')
                              ->getRepository('RZ\Renzo\Core\Entities\NodeType')
                              ->findOneByName('Page');
        $node = new Node($nodeType);
        $node->setNodeName($array['title']);
        $node->setPublished(true);

        Kernel::getService('em')->persist($node);

        $tran = Kernel::getService('em')
                          ->getRepository('RZ\Renzo\Core\Entities\Translation')
                          ->findDefault();
        $src = new NSPage($node, $tran);
        $src->setTitle($array['title']);
        $src->setContent($array['content']);

        Kernel::getService('em')->persist($src);

        return $node;
    }

    /**
     * Import Nodes file.
     *
     *
     * @return string
     */
    public static function importNodesAction(Request $request)
    {
        $data = array();
        $data['status'] = false;

        $allNode = Kernel::getService('em')
                         ->getRepository('RZ\Renzo\Core\Entities\Node')
                         ->findAll();
        try {
            if (empty($allNode)) {
                $home = array(
                    'title' => 'Home',
                    'content' => 'sample content'
                );
                $about = array(
                    'title' => 'About',
                    'content' => 'sample about'
                );
                $contact = array(
                    'title' => 'Contact',
                    'content' => 'Contact RZ team for more awesome stuff'
                );

                $homeNode = static::createNode($home);
                $aboutNode = static::createNode($about);
                $contactNode = static::createNode($contact);

                $homeNode->setHome(true);
                $aboutNode->setParent($homeNode);
                $contactNode->setParent($homeNode);

                Kernel::getService('em')->flush();
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
                $path = RENZO_ROOT . '/themes/Install' . $pathFile;
            } else {
                $theme = Kernel::getService('em')
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
