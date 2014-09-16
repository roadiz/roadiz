<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file NodeTypesUtilsController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */

namespace Themes\Rozier\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\Entities\NodeTypeField;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Handlers\NodeTypeHandler;
use RZ\Renzo\Core\Serializers\NodeTypeJsonSerializer;
use Themes\Rozier\RozierApp;

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
        $nodeType = $this->getKernel()->em()
            ->find('RZ\Renzo\Core\Entities\NodeType', (int) $nodeTypeId);

        $response =  new Response(
            NodeTypeJsonSerializer::serialize($noteType),
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
        $form = $this->buildImportJsonFileForm();

        $form->handleRequest();

        if ($form->isValid() &&
            !empty($form['node_type_file'])) {

            $file = $form['node_type_file']->getData();

            if (UPLOAD_ERR_OK == $file['error']) {

                $serializedData = file_get_contents($file['tmp_name']);

                if (null !== json_decode($serializedData)) {

                    $nodeType = NodeTypeJsonSerializer::deserialize($serializedData);
                    $existingNT = $this->getKernel()->em()
                        ->getRepository('RZ\Renzo\Core\Entities\NodeType')
                        ->findOneBy(array('name'=>$nodeType->getName()));

                    if (null === $existingNT) {
                        /*
                         * New node-type…
                         *
                         * First persist node-type
                         */
                        $this->getKernel()->em()->persist($nodeType);

                        // Flush before creating node-type fields.
                        $this->getKernel()->em()->flush();

                        foreach ($nodeType->getFields() as $field) {
                            /*
                             * then persist each field
                             */
                            $field->setNodeType($nodeType);
                            $this->getKernel()->em()->persist($field);
                        }

                        $msg = $this->getTranslator()->trans('nodeType.imported.created');
                        $request->getSession()->getFlashBag()->add('confirm', $msg);
                        $this->getLogger()->info($msg);

                    } else {
                        /*
                         * Node-type already exists.
                         * Must update fields.
                         */
                        $existingNT->getHandler()->diff($nodeType);

                        $msg = $this->getTranslator()->trans('nodeType.imported.updated');
                        $request->getSession()->getFlashBag()->add('confirm', $msg);
                        $this->getLogger()->info($msg);
                    }

                    $this->getKernel()->em()->flush();
                    $nodeType->getHandler()->updateSchema();

                    /*
                     * Redirect to update schema page
                     */
                    $response = new RedirectResponse(
                        $this->getKernel()->getUrlGenerator()->generate(
                            'nodeTypesSchemaUpdate',
                            array(
                                '_token' => $this->getKernel()->getCsrfProvider()->generateCsrfToken(static::SCHEMA_TOKEN_INTENTION)
                            )
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
                            'nodeTypesImportPage'
                        )
                    );
                    $response->prepare($request);

                    return $response->send();
                }
            } else {
                $msg = $this->getTranslator()->trans('file.not_uploaded');
                $request->getSession()->getFlashBag()->add('error', $msg);
                $this->getLogger()->error($msg);

                // redirect even if its null
                $response = new RedirectResponse(
                    $this->getKernel()->getUrlGenerator()->generate(
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
        $builder = $this->getFormFactory()
            ->createBuilder('form')
            ->add('node_type_file', 'file');

        return $builder->getForm();
    }
}
