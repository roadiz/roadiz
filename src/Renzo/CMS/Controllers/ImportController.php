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

class ImportController extends AppController
{

    static function importSettingsAction($themeId = null)
    {
        $data = {};
        $data['status'] = true;
        try {
            if (null !== $themeId) {
                $path = REZ0_ROOT.'/themes/Intall/Resources/import/settings.rzt';
            }
            else {
                $theme = Kernel::getInstance()->em()
                         ->getRepository('RZ\Renzo\Core\Entities\Theme')
                         ->findOneBy(array('id'=>$themeId));
                $dir = dir($theme->getClassName());
                if ($theme === null)
                    throw new Exception('Theme don\'t exist in database.');
                $path = REZ0_ROOT.'/themes/' . $dir . '/Resources/import/settings.rzt';
            }
            $file = file_get_contents($path);
            if ($file === false) {
                throw new Exception('File: '. $settings . ' don\'t exist.');
            }
            else {
                $ret = SettingsImporter::importJsonFile($file);
            }
        }
        catch (\Exception $e) {
            $data['error'] = $e->getMessage();
            $date['status'] = false;
            return new Response(
                json_encode($data),
                Response::HTTP_NOT_FOUND,
                array('content-type' => 'application/javascript')
            );
        }
        return new Response(
            json_encode($data),
            Response::HTTP_OK,
            array('content-type' => 'application/javascript')
        );
    }

}