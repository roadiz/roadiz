<?php
/**
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

use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Handlers\NodeTypeHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
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

        /** @var NodeType $nodeType */
        $nodeType = $this->get('em')
                         ->find('RZ\Roadiz\Core\Entities\NodeType', $nodeTypeId);

        if ($nodeType !== null) {
            $fields = $nodeType->getFields();

            $this->assignation['nodeType'] = $nodeType;
            $this->assignation['fields'] = $fields;

            return $this->render('node-type-fields/list.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
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

        /** @var NodeTypeField $field */
        $field = $this->get('em')
                      ->find('RZ\Roadiz\Core\Entities\NodeTypeField', $nodeTypeFieldId);

        if ($field !== null) {
            $this->assignation['nodeType'] = $field->getNodeType();
            $this->assignation['field'] = $field;

            $form = $this->createForm(new NodeTypeFieldType(), $field, [
                'em' => $this->get('em'),
                'fieldName' => $field->getName(),
                'nodeType' => $field->getNodeType(),
            ]);
            $form->handleRequest($request);

            if ($form->isValid()) {
                $this->get('em')->flush();

                /** @var NodeTypeHandler $handler */
                $handler = $this->get('node_type.handler');
                $handler->setNodeType($field->getNodeType());
                $handler->updateSchema();

                $msg = $this->getTranslator()->trans('nodeTypeField.%name%.updated', ['%name%' => $field->getName()]);
                $this->publishConfirmMessage($request, $msg);

                /*
                 * Redirect to update schema page
                 */
                return $this->redirect($this->generateUrl(
                    'nodeTypesFieldSchemaUpdate',
                    [
                        'nodeTypeId' => $field->getNodeType()->getId(),
                    ]
                ));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('node-type-fields/edit.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
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
        /** @var NodeType $nodeType */
        $nodeType = $this->get('em')
                         ->find('RZ\Roadiz\Core\Entities\NodeType', $nodeTypeId);


        if ($nodeType !== null &&
            $field !== null) {
            $latestPosition = $this->get('em')
                                   ->getRepository('RZ\Roadiz\Core\Entities\NodeTypeField')
                                   ->findLatestPositionInNodeType($nodeType);
            $field->setNodeType($nodeType);
            $field->setPosition($latestPosition + 1);

            $this->assignation['nodeType'] = $nodeType;
            $this->assignation['field'] = $field;

            $form = $this->createForm(new NodeTypeFieldType(), $field, [
                'em' => $this->get('em'),
                'nodeType' => $field->getNodeType(),
            ]);
            $form->handleRequest($request);

            if ($form->isValid()) {
                try {
                    $this->get('em')->persist($field);
                    $this->get('em')->flush();
                    $this->get('em')->refresh($nodeType);

                    /** @var NodeTypeHandler $handler */
                    $handler = $this->get('node_type.handler');
                    $handler->setNodeType($nodeType);
                    $handler->updateSchema();

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
                        ]
                    ));
                } catch (\Exception $e) {
                    $msg = $e->getMessage();
                    $request->getSession()->getFlashBag()->add('error', $msg);
                    $this->get('logger')->error($msg);
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
        }

        throw new ResourceNotFoundException();
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

        /** @var NodeTypeField $field */
        $field = $this->get('em')
                      ->find('RZ\Roadiz\Core\Entities\NodeTypeField', (int) $nodeTypeFieldId);

        if ($field !== null) {
            $this->assignation['field'] = $field;
            $form = $this->buildDeleteForm($field);
            $form->handleRequest($request);

            if ($form->isValid() &&
                $form->getData()['nodeTypeFieldId'] == $field->getId()) {
                $nodeTypeId = $field->getNodeType()->getId();
                $this->get('em')->remove($field);
                $this->get('em')->flush();

                /*
                 * Update Database
                 */
                /** @var NodeType $nodeType */
                $nodeType = $this->get('em')
                                 ->find('RZ\Roadiz\Core\Entities\NodeType', (int) $nodeTypeId);

                /** @var NodeTypeHandler $handler */
                $handler = $this->get('node_type.handler');
                $handler->setNodeType($nodeType);
                $handler->updateSchema();

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
                    ]
                ));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('node-type-fields/delete.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
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
