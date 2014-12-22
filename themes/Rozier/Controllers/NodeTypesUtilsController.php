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
 * @file NodeTypesUtilsController.php
 * @author Ambroise Maupate
 */

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Serializers\NodeTypeJsonSerializer;
use Themes\Rozier\RozierApp;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * {@inheritdoc}
 */
class NodeTypesUtilsController extends RozierApp
{
    /**
     * Export a Json file containing NodeType datas and fields.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $nodeTypeId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function exportJsonFileAction(Request $request, $nodeTypeId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODETYPES');

        $nodeType = $this->getService('em')
            ->find('RZ\Roadiz\Core\Entities\NodeType', (int) $nodeTypeId);

        $response =  new Response(
            NodeTypeJsonSerializer::serialize($nodeType),
            Response::HTTP_OK,
            array()
        );

        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $nodeType->getName() . '.rzt'
            )
        ); // Rezo-Zero Type

        $response->prepare($request);

        return $response;
    }

    /**
     * Import a Json file (.rzt) containing NodeType datas and fields.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function importJsonFileAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODETYPES');

        $form = $this->buildImportJsonFileForm();

        $form->handleRequest();

        if ($form->isValid() &&
            !empty($form['node_type_file'])) {
            $file = $form['node_type_file']->getData();

            if (UPLOAD_ERR_OK == $file['error']) {
                $serializedData = file_get_contents($file['tmp_name']);

                if (null !== json_decode($serializedData)) {
                    $nodeType = NodeTypeJsonSerializer::deserialize($serializedData);
                    $existingNT = $this->getService('em')
                        ->getRepository('RZ\Roadiz\Core\Entities\NodeType')
                        ->findOneBy(array('name'=>$nodeType->getName()));

                    if (null === $existingNT) {
                        /*
                         * New node-type…
                         *
                         * First persist node-type
                         */
                        $this->getService('em')->persist($nodeType);

                        // Flush before creating node-type fields.
                        $this->getService('em')->flush();

                        foreach ($nodeType->getFields() as $field) {
                            /*
                             * then persist each field
                             */
                            $field->setNodeType($nodeType);
                            $this->getService('em')->persist($field);
                        }

                        $msg = $this->getTranslator()->trans('nodeType.imported.created');
                        $request->getSession()->getFlashBag()->add('confirm', $msg);
                        $this->getService('logger')->info($msg);

                    } else {
                        /*
                         * Node-type already exists.
                         * Must update fields.
                         */
                        $existingNT->getHandler()->diff($nodeType);

                        $msg = $this->getTranslator()->trans('nodeType.imported.updated');
                        $request->getSession()->getFlashBag()->add('confirm', $msg);
                        $this->getService('logger')->info($msg);
                    }

                    $this->getService('em')->flush();
                    $nodeType->getHandler()->updateSchema();

                    /*
                     * Redirect to update schema page
                     */
                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'nodeTypesSchemaUpdate',
                            array(
                                '_token' => $this->getService('csrfProvider')->generateCsrfToken(static::SCHEMA_TOKEN_INTENTION)
                            )
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
                            'nodeTypesImportPage'
                        )
                    );
                    $response->prepare($request);

                    return $response->send();
                }
            } else {
                $msg = $this->getTranslator()->trans('file.not_uploaded');
                $request->getSession()->getFlashBag()->add('error', $msg);
                $this->getService('logger')->error($msg);

                // redirect even if its null
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate(
                        'nodeTypesImportPage'
                    )
                );
                $response->prepare($request);

                return $response->send();
            }
        }

        $this->assignation['form'] = $form->createView();

        return new Response(
            $this->getTwig()->render('node-types/import.html.twig', $this->assignation),
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
            ->add('node_type_file', 'file', array(
                'label' => $this->getTranslator()->trans('nodeType.file'),
            ));

        return $builder->getForm();
    }
}
