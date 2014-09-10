<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file GroupsUtilsController.php
 * @copyright REZO ZERO 2014
 * @author Thomas Aufresne
 */

namespace Themes\Rozier\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Role;
use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\Serializers\RoleJsonSerializer;
use RZ\Renzo\Core\Serializers\RoleCollectionJsonSerializer;
use Themes\Rozier\RozierApp;

use RZ\Renzo\CMS\Importers\RolesImporter;

use RZ\Renzo\Core\Exceptions\EntityAlreadyExistsException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use \Symfony\Component\Form\Form;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * {@inheritdoc}
 */
class RolesUtilsController extends RozierApp
{
    /**
     * Export all Group datas and roles in a Json file (.rzt).
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function exportAllAction(Request $request)
    {
        $existingRole = $this->getKernel()->em()
                              ->getRepository('RZ\Renzo\Core\Entities\Role')
                              ->findAll();
        $role = RoleCollectionJsonSerializer::serialize($existingRole);

        $response =  new Response(
            $role,
            Response::HTTP_OK,
            array()
        );

        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'role-all-' . date("YmdHis")  . '.rzt')); // Rezo-Zero Type

        $response->prepare($request);

        return $response;
    }

    /**
     * Export a Role in a Json file (.rzt).
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $roleId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function exportAction(Request $request, $roleId)
    {
        $existingRole= $this->getKernel()->em()
                              ->find('RZ\Renzo\Core\Entities\Role', (int) $roleId);

        $role = RoleCollectionJsonSerializer::serialize(array($existingRole));

        $response =  new Response(
            $role,
            Response::HTTP_OK,
            array()
        );

        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'role-' . $existingRole->getName() . '-' . date("YmdHis")  . '.rzt')); // Rezo-Zero Type

        $response->prepare($request);

        return $response;
    }

    /**
     * Import a Json file (.rzt) containing Roles.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function importJsonFileAction(Request $request)
    {
        $form = $this->buildImportJsonFileForm();

        $form->handleRequest();

        if ($form->isValid() &&
            !empty($form['role_file'])) {

            $file = $form['role_file']->getData();

            if (UPLOAD_ERR_OK == $file['error']) {

                $serializedData = file_get_contents($file['tmp_name']);

                if (null !== json_decode($serializedData)) {
                    if (RolesImporter::importJsonFile($serializedData)) {
                        $msg = $this->getTranslator()->trans('role.imported');
                        $request->getSession()->getFlashBag()->add('confirm', $msg);
                        $this->getLogger()->info($msg);

                        $this->getKernel()->em()->flush();

                         // redirect even if its null
                        $response = new RedirectResponse(
                            $this->getKernel()->getUrlGenerator()->generate(
                                'rolesHomePage'
                            )
                        );
                        $response->prepare($request);

                        return $response->send();
                    } else {
                        $msg = $this->getTranslator()->trans('file.format.not_valid');
                        $request->getSession()->getFlashBag()->add('error', $msg);
                        $this->getLogger()->error($msg);

                        // redirect even if its null
                        $response = new RedirectResponse(
                            $this->getKernel()->getUrlGenerator()->generate(
                                'rolesImportPage'
                            )
                        );
                        $response->prepare($request);

                        return $response->send();
                    }


                } else {
                    $msg = $this->getTranslator()->trans('file.format.not_valid');
                    $request->getSession()->getFlashBag()->add('error', $msg);
                    $this->getLogger()->error($msg);

                    // redirect even if its null
                    $response = new RedirectResponse(
                        $this->getKernel()->getUrlGenerator()->generate(
                            'rolesImportPage'
                        )
                    );
                    $response->prepare($request);

                    return $response->send();
                }
            } else {
                $msg = $this->getTranslator()->trans('file.not_uploaded');
                $request->getSession()->getFlashBag()->add('error', $msg);
                $this->getLogger()->error($msg);
            }
        }

        $this->assignation['form'] = $form->createView();

        return new Response(
            $this->getTwig()->render('roles/import.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    private function buildImportJsonFileForm()
    {
        $builder = $this->getFormFactory()
            ->createBuilder('form')
            ->add('role_file', 'file');

        return $builder->getForm();
    }
}