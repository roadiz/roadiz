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
 * @file NodeTypeFieldsController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Entities\NodeTypeField;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;
use Themes\Rozier\Forms\NodeTypeFieldType;
use Themes\Rozier\RozierApp;

/**
 * {@inheritdoc}
 */
class NodeTypeFieldsController extends RozierApp
{
    /**
     * List every node-type-fields.
     *
     * @param Request $request
     * @param int     $nodeTypeId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(Request $request, $nodeTypeId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODETYPES');

        $nodeType = $this->getService('em')
                         ->find('RZ\Roadiz\Core\Entities\NodeType', $nodeTypeId);

        if ($nodeType !== null) {
            $fields = $nodeType->getFields();

            $this->assignation['nodeType'] = $nodeType;
            $this->assignation['fields'] = $fields;

            return $this->render('node-type-fields/list.html.twig', $this->assignation);
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return an edition form for requested node-type.
     *
     * @param Request $request
     * @param int     $nodeTypeFieldId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $nodeTypeFieldId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODETYPES');

        $field = $this->getService('em')
                      ->find('RZ\Roadiz\Core\Entities\NodeTypeField', $nodeTypeFieldId);

        if ($field !== null) {
            $this->assignation['nodeType'] = $field->getNodeType();
            $this->assignation['field'] = $field;

            $form = $this->createForm(new NodeTypeFieldType(), $field, [
                'em' => $this->getService('em'),
                'fieldName' => $field->getName(),
                'nodeType' => $field->getNodeType(),
            ]);
            $form->handleRequest($request);

            if ($form->isValid()) {
                $this->getService('em')->flush();
                $field->getNodeType()->getHandler()->updateSchema();

                $msg = $this->getTranslator()->trans('nodeTypeField.%name%.updated', ['%name%' => $field->getName()]);
                $this->publishConfirmMessage($request, $msg);

                /*
                 * Redirect to update schema page
                 */
                return $this->redirect($this->generateUrl(
                    'nodeTypesFieldSchemaUpdate',
                    [
                        'nodeTypeId' => $field->getNodeType()->getId(),
                        '_token' => $this->getService('csrfTokenManager')->getToken(
                            static::SCHEMA_TOKEN_INTENTION
                        ),
                    ]
                ));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('node-type-fields/edit.html.twig', $this->assignation);
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return an creation form for requested node-type.
     *
     * @param Request $request
     * @param int     $nodeTypeId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request, $nodeTypeId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODETYPES');

        $field = new NodeTypeField();
        $nodeType = $this->getService('em')
                         ->find('RZ\Roadiz\Core\Entities\NodeType', $nodeTypeId);


        if ($nodeType !== null &&
            $field !== null) {
            $latestPosition = $this->getService('em')
                                   ->getRepository('RZ\Roadiz\Core\Entities\NodeTypeField')
                                   ->findLatestPositionInNodeType($nodeType);
            $field->setNodeType($nodeType);
            $field->setPosition($latestPosition + 1);

            $this->assignation['nodeType'] = $nodeType;
            $this->assignation['field'] = $field;

            $form = $this->createForm(new NodeTypeFieldType(), $field, [
                'em' => $this->getService('em'),
                'nodeType' => $field->getNodeType(),
            ]);
            $form->handleRequest($request);

            if ($form->isValid()) {
                try {
                    $this->getService('em')->persist($field);
                    $this->getService('em')->flush();
                    $this->getService('em')->refresh($nodeType);

                    $nodeType->getHandler()->updateSchema();

                    $msg = $this->getTranslator()->trans(
                        'nodeTypeField.%name%.created',
                        ['%name%' => $field->getName()]
                    );
                    $this->publishConfirmMessage($request, $msg);

                    /*
                     * Redirect to update schema page
                     */
                    return $this->redirect($this->generateUrl(
                        'nodeTypesFieldSchemaUpdate',
                        [
                            'nodeTypeId' => $nodeTypeId,
                            '_token' => $this->getService('csrfTokenManager')->getToken(
                                static::SCHEMA_TOKEN_INTENTION
                            ),
                        ]
                    ));
                } catch (\Exception $e) {
                    $msg = $e->getMessage();
                    $request->getSession()->getFlashBag()->add('error', $msg);
                    $this->getService('logger')->error($msg);
                    /*
                     * Redirect to add page
                     */
                    return $this->redirect($this->generateUrl(
                        'nodeTypeFieldsAddPage',
                        ['nodeTypeId' => $nodeTypeId]
                    ));
                }
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('node-type-fields/add.html.twig', $this->assignation);
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return an deletion form for requested node.
     *
     * @param Request $request
     * @param int     $nodeTypeFieldId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $nodeTypeFieldId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODEFIELDS_DELETE');

        $field = $this->getService('em')
                      ->find('RZ\Roadiz\Core\Entities\NodeTypeField', (int) $nodeTypeFieldId);

        if ($field !== null) {
            $this->assignation['field'] = $field;
            $form = $this->buildDeleteForm($field);
            $form->handleRequest($request);

            if ($form->isValid() &&
                $form->getData()['nodeTypeFieldId'] == $field->getId()) {
                $nodeTypeId = $field->getNodeType()->getId();
                $this->getService('em')->remove($field);
                $this->getService('em')->flush();

                /*
                 * Update Database
                 */
                $nodeType = $this->getService('em')
                                 ->find('RZ\Roadiz\Core\Entities\NodeType', (int) $nodeTypeId);

                $nodeType->getHandler()->updateSchema();

                $msg = $this->getTranslator()->trans(
                    'nodeTypeField.%name%.deleted',
                    ['%name%' => $field->getName()]
                );
                $this->publishConfirmMessage($request, $msg);

                /*
                 * Redirect to update schema page
                 */
                return $this->redirect($this->generateUrl(
                    'nodeTypesFieldSchemaUpdate',
                    [
                        'nodeTypeId' => $nodeTypeId,
                        '_token' => $this->getService('csrfTokenManager')->getToken(
                            static::SCHEMA_TOKEN_INTENTION
                        ),
                    ]
                ));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('node-type-fields/delete.html.twig', $this->assignation);
        } else {
            return $this->throw404();
        }
    }

    /**
     * @param NodeTypeField $field
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildDeleteForm(NodeTypeField $field)
    {
        $builder = $this->createFormBuilder()
                        ->add('nodeTypeFieldId', 'hidden', [
                            'data' => $field->getId(),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ]);

        return $builder->getForm();
    }
}
