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

use RZ\Roadiz\Core\Serializers\GroupCollectionJsonSerializer;
use RZ\Roadiz\CMS\Importers\GroupsImporter;
use Themes\Rozier\RozierApp;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * {@inheritdoc}
 */
class GroupsUtilsController extends RozierApp
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
        $this->validateAccessForRole('ROLE_ACCESS_GROUPS');

        $existingGroup = $this->getService('em')
                              ->getRepository('RZ\Roadiz\Core\Entities\Group')
                              ->findAll();
        $group = GroupCollectionJsonSerializer::serialize($existingGroup);

        $response =  new Response(
            $group,
            Response::HTTP_OK,
            array()
        );

        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                'group-all-' . date("YmdHis")  . '.rzt'
            )
        ); // Rezo-Zero Type

        $response->prepare($request);

        return $response;
    }

    /**
     * Export a Group in a Json file (.rzt).
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $groupId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function exportAction(Request $request, $groupId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_GROUPS');

        $existingGroup = $this->getService('em')
                              ->find('RZ\Roadiz\Core\Entities\Group', (int) $groupId);

        $group = GroupCollectionJsonSerializer::serialize(array($existingGroup));

        $response =  new Response(
            $group,
            Response::HTTP_OK,
            array()
        );

        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                'group-' . $existingGroup->getName() . '-' . date("YmdHis")  . '.rzt'
            )
        ); // Rezo-Zero Type

        $response->prepare($request);

        return $response;
    }

    /**
     * Import a Json file (.rzt) containing Group datas and roles.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function importJsonFileAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_GROUPS');

        $form = $this->buildImportJsonFileForm();

        $form->handleRequest();

        if ($form->isValid() &&
            !empty($form['group_file'])) {

            $file = $form['group_file']->getData();

            if (UPLOAD_ERR_OK == $file['error']) {

                $serializedData = file_get_contents($file['tmp_name']);

                if (null !== json_decode($serializedData)) {

                    GroupsImporter::importJsonFile($serializedData);

                    $msg = $this->getTranslator()->trans('group.imported.updated');
                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getService('logger')->info($msg);

                     // redirect even if its null
                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'groupsHomePage'
                        )
                    );
                    $response->prepare($request);

                    return $response->send();

                } else {
                    $msg = $this->getTranslator()->trans('file.format.not_valid');
                    $request->getSession()->getFlashBag()->add('error', $msg);
                    $this->getService('logger')->error($msg);

                    // redirect even if its null
                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'groupsImportPage'
                        )
                    );
                    $response->prepare($request);

                    return $response->send();
                }
            } else {
                $msg = $this->getTranslator()->trans('file.not_uploaded');
                $request->getSession()->getFlashBag()->add('error', $msg);
                $this->getService('logger')->error($msg);
            }
        }

        $this->assignation['form'] = $form->createView();

        return new Response(
            $this->getTwig()->render('groups/import.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    private function buildImportJsonFileForm()
    {
        $builder = $this->getService('formFactory')
            ->createBuilder('form')
            ->add('group_file', 'file', array(
                'label' => $this->getTranslator()->trans('group.file')
            ));

        return $builder->getForm();
    }
}
