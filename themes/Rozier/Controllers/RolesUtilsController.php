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
 * @file GroupsUtilsController.php
 * @author Thomas Aufresne
 */

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\CMS\Importers\RolesImporter;
use RZ\Roadiz\Core\Serializers\RoleCollectionJsonSerializer;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Themes\Rozier\RozierApp;

/**
 * {@inheritdoc}
 */
class RolesUtilsController extends RozierApp
{
    /**
     * Export all Group datas and roles in a Json file (.rzt).
     *
     * @param Request $request
     *
     * @return Response
     */
    public function exportAllAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_ROLES');

        $existingRole = $this->get('em')
                             ->getRepository('RZ\Roadiz\Core\Entities\Role')
                             ->findAll();

        $serializer = new RoleCollectionJsonSerializer($this->get('em'));
        $role = $serializer->serialize($existingRole);

        $response = new Response(
            $role,
            Response::HTTP_OK,
            []
        );

        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                'role-all-' . date("YmdHis") . '.rzt'
            )
        ); // Rezo-Zero Type

        $response->prepare($request);

        return $response;
    }

    /**
     * Export a Role in a Json file (.rzt).
     *
     * @param Request $request
     * @param int     $roleId
     *
     * @return Response
     */
    public function exportAction(Request $request, $roleId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_ROLES');

        $existingRole = $this->get('em')
                             ->find('RZ\Roadiz\Core\Entities\Role', (int) $roleId);

        $serializer = new RoleCollectionJsonSerializer($this->get('em'));
        $role = $serializer->serialize([$existingRole]);

        $response = new Response(
            $role,
            Response::HTTP_OK,
            []
        );

        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                'role-' . $existingRole->getName() . '-' . date("YmdHis") . '.rzt'
            )
        ); // Rezo-Zero Type

        $response->prepare($request);

        return $response;
    }

    /**
     * Import a Json file (.rzt) containing Roles.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function importJsonFileAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_ROLES');

        $form = $this->buildImportJsonFileForm();

        $form->handleRequest($request);

        if ($form->isValid() &&
            !empty($form['role_file'])) {
            $file = $form['role_file']->getData();

            if ($file->isValid()) {
                $serializedData = file_get_contents($file->getPathname());

                if (null !== json_decode($serializedData)) {
                    if (RolesImporter::importJsonFile($serializedData, $this->get('em'), $this->get('factory.handler'))) {
                        $msg = $this->getTranslator()->trans('role.imported');
                        $this->publishConfirmMessage($request, $msg);

                        $this->get('em')->flush();

                        // Clear result cache
                        $cacheDriver = $this->get('em')->getConfiguration()->getResultCacheImpl();
                        if ($cacheDriver !== null) {
                            $cacheDriver->deleteAll();
                        }

                        // redirect even if its null
                        return $this->redirect($this->generateUrl(
                            'rolesHomePage'
                        ));
                    } else {
                        $msg = $this->getTranslator()->trans('file.format.not_valid');
                        $request->getSession()->getFlashBag()->add('error', $msg);
                        $this->get('logger')->error($msg);

                        // redirect even if its null
                        return $this->redirect($this->generateUrl(
                            'rolesImportPage'
                        ));
                    }
                } else {
                    $msg = $this->getTranslator()->trans('file.format.not_valid');
                    $request->getSession()->getFlashBag()->add('error', $msg);
                    $this->get('logger')->error($msg);

                    // redirect even if its null
                    return $this->redirect($this->generateUrl(
                        'rolesImportPage'
                    ));
                }
            } else {
                $msg = $this->getTranslator()->trans('file.not_uploaded');
                $request->getSession()->getFlashBag()->add('error', $msg);
                $this->get('logger')->error($msg);
            }
        }

        $this->assignation['form'] = $form->createView();

        return $this->render('roles/import.html.twig', $this->assignation);
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    private function buildImportJsonFileForm()
    {
        $builder = $this->createFormBuilder()
                        ->add('role_file', FileType::class, [
                            'label' => 'role.file',
                        ]);

        return $builder->getForm();
    }
}
