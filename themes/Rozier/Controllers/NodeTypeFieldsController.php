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

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use RZ\Roadiz\Core\Exceptions\ReservedSQLWordException;
use Themes\Rozier\RozierApp;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use \Symfony\Component\Validator\Constraints\NotBlank;

/**
 * {@inheritdoc}
 */
class NodeTypeFieldsController extends RozierApp
{
    /**
     * List every node-type-fields.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $nodeTypeId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function listAction(Request $request, $nodeTypeId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODETYPES');

        $nodeType = $this->getService('em')
            ->find('RZ\Roadiz\Core\Entities\NodeType', (int) $nodeTypeId);

        if ($nodeType !== null) {
            $fields = $nodeType->getFields();

            $this->assignation['nodeType'] = $nodeType;
            $this->assignation['fields'] = $fields;

            return new Response(
                $this->getTwig()->render('node-type-fields/list.html.twig', $this->assignation),
                Response::HTTP_OK,
                ['content-type' => 'text/html']
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return an edition form for requested node-type.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $nodeTypeFieldId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $nodeTypeFieldId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODETYPES');

        $field = $this->getService('em')
            ->find('RZ\Roadiz\Core\Entities\NodeTypeField', (int) $nodeTypeFieldId);

        if ($field !== null) {
            $this->assignation['nodeType'] = $field->getNodeType();
            $this->assignation['field'] = $field;
            $form = $this->buildEditForm($field);
            $form->handleRequest();

            if ($form->isValid()) {
                $this->editNodeTypeField($form->getData(), $field);

                $msg = $this->getTranslator()->trans('nodeTypeField.%name%.updated', ['%name%'=>$field->getName()]);
                $this->publishConfirmMessage($request, $msg);

                /*
                 * Redirect to update schema page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate(
                        'nodeTypesFieldSchemaUpdate',
                        [
                            'nodeTypeId' => $field->getNodeType()->getId(),
                            '_token' => $this->getService('csrfProvider')->generateCsrfToken(
                                static::SCHEMA_TOKEN_INTENTION
                            )
                        ]
                    )
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('node-type-fields/edit.html.twig', $this->assignation),
                Response::HTTP_OK,
                ['content-type' => 'text/html']
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return an creation form for requested node-type.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $nodeTypeId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request, $nodeTypeId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODETYPES');

        $field = new NodeTypeField();
        $nodeType = $this->getService('em')
            ->find('RZ\Roadiz\Core\Entities\NodeType', (int) $nodeTypeId);

        if ($nodeType !== null &&
            $field !== null) {
            $this->assignation['nodeType'] = $nodeType;
            $this->assignation['field'] = $field;
            $form = $this->buildEditForm($field);
            $form->handleRequest();

            if ($form->isValid()) {
                try {
                    $this->addNodeTypeField($form->getData(), $field, $nodeType);

                    $msg = $this->getTranslator()->trans(
                        'nodeTypeField.%name%.created',
                        ['%name%'=>$field->getName()]
                    );
                    $this->publishConfirmMessage($request, $msg);


                    /*
                     * Redirect to update schema page
                     */
                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'nodeTypesFieldSchemaUpdate',
                            [
                                'nodeTypeId' => $nodeTypeId,
                                '_token' => $this->getService('csrfProvider')->generateCsrfToken(
                                    static::SCHEMA_TOKEN_INTENTION
                                )
                            ]
                        )
                    );

                } catch (\Exception $e) {
                    $msg = $e->getMessage();
                    $request->getSession()->getFlashBag()->add('error', $msg);
                    $this->getService('logger')->error($msg);
                    /*
                     * Redirect to add page
                     */
                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'nodeTypeFieldsAddPage',
                            ['nodeTypeId' => $nodeTypeId]
                        )
                    );
                }

                $response->prepare($request);
                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('node-type-fields/add.html.twig', $this->assignation),
                Response::HTTP_OK,
                ['content-type' => 'text/html']
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return an deletion form for requested node.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $nodeTypeFieldId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $nodeTypeFieldId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODEFIELDS_DELETE');

        $field = $this->getService('em')
            ->find('RZ\Roadiz\Core\Entities\NodeTypeField', (int) $nodeTypeFieldId);

        if ($field !== null) {
            $this->assignation['field'] = $field;
            $form = $this->buildDeleteForm($field);
            $form->handleRequest();

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

                $nodeType->getHandler()->regenerateEntityClass();

                $msg = $this->getTranslator()->trans(
                    'nodeTypeField.%name%.deleted',
                    ['%name%'=>$field->getName()]
                );
                $this->publishConfirmMessage($request, $msg);

                /*
                 * Redirect to update schema page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate(
                        'nodeTypesFieldSchemaUpdate',
                        [
                            'nodeTypeId' => $nodeTypeId,
                            '_token' => $this->getService('csrfProvider')->generateCsrfToken(
                                static::SCHEMA_TOKEN_INTENTION
                            )
                        ]
                    )
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('node-type-fields/delete.html.twig', $this->assignation),
                Response::HTTP_OK,
                ['content-type' => 'text/html']
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * @param array                                $data
     * @param RZ\Roadiz\Core\Entities\NodeTypeField $field
     */
    private function editNodeTypeField($data, NodeTypeField $field)
    {
        foreach ($data as $key => $value) {
            $setter = 'set'.ucwords($key);
            $field->$setter($value);
        }

        $this->getService('em')->flush();
        $field->getNodeType()->getHandler()->updateSchema();
    }

    /**
     * @param array                                $data
     * @param RZ\Roadiz\Core\Entities\NodeTypeField $field
     * @param RZ\Roadiz\Core\Entities\NodeType      $nodeType
     */
    private function addNodeTypeField(
        $data,
        NodeTypeField $field,
        NodeType $nodeType
    ) {

        /*
         * Check reserved words
         */
        if (in_array(strtolower($data['name']), NodeTypeField::$forbiddenNames)) {
            throw new ReservedSQLWordException($this->getTranslator()->trans(
                "%field%.is.reserved.word",
                ['%field%' => $data['name']]
            ), 1);
        }

        /*
         * Check existing
         */
        $existing = $this->getService('em')
                         ->getRepository('RZ\Roadiz\Core\Entities\NodeTypeField')
                         ->findOneBy([
                            'name' => $data['name'],
                            'nodeType' => $nodeType
                         ]);
        if (null !== $existing) {
            throw new EntityAlreadyExistsException($this->getTranslator()->trans(
                "%field%.already_exists",
                ['%field%' => $data['name']]
            ), 1);
        }

        foreach ($data as $key => $value) {
            $setter = 'set'.ucwords($key);
            $field->$setter($value);
        }

        $field->setNodeType($nodeType);
        $this->getService('em')->persist($field);

        $nodeType->addField($field);
        $this->getService('em')->flush();

        $nodeType->getHandler()->regenerateEntityClass();
    }

    /**
     * @param RZ\Roadiz\Core\Entities\NodeTypeField $field
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildEditForm(NodeTypeField $field)
    {
        $defaults = [
            'name' =>           $field->getName(),
            'label' =>          $field->getLabel(),
            'type' =>           $field->getType(),
            'description' =>    $field->getDescription(),
            'visible' =>        $field->isVisible(),
            'indexed' =>        $field->isIndexed(),
            'defaultValues' =>  $field->getDefaultValues(),
            'minLength' =>      $field->getMinLength(),
            'maxLength' =>      $field->getMaxLength(),
        ];
        $builder = $this->getService('formFactory')
                    ->createBuilder('form', $defaults)
                    ->add('name', 'text', [
                        'label' => $this->getTranslator()->trans('name'),
                        'constraints' => [
                            new NotBlank()
                        ]
                    ])
                    ->add('label', 'text', [
                        'label' => $this->getTranslator()->trans('label'),
                        'constraints' => [
                            new NotBlank()
                        ]
                    ])
                    ->add('type', 'choice', [
                        'label' => $this->getTranslator()->trans('type'),
                        'required' => true,
                        'choices' => NodeTypeField::$typeToHuman
                    ])
                    ->add('description', 'text', [
                        'label' => $this->getTranslator()->trans('description'),
                        'required' => false
                    ])
                    ->add('visible', 'checkbox', [
                        'label' => $this->getTranslator()->trans('visible'),
                        'required' => false
                    ])
                    ->add('indexed', 'checkbox', [
                        'label' => $this->getTranslator()->trans('indexed'),
                        'required' => false
                    ])
                    ->add(
                        'defaultValues',
                        'text',
                        [
                            'label' => $this->getTranslator()->trans('defaultValues'),
                            'required' => false,
                            'attr' => [
                                'placeholder' => $this->getTranslator()->trans('enter_values_comma_separated')
                            ]
                        ]
                    )
                    ->add(
                        'minLength',
                        'integer',
                        [
                            'label' => $this->getTranslator()->trans('nodeTypeField.minLength'),
                            'required' => false
                        ]
                    )
                    ->add(
                        'maxLength',
                        'integer',
                        [
                            'label' => $this->getTranslator()->trans('nodeTypeField.maxLength'),
                            'required' => false
                        ]
                    );

        return $builder->getForm();
    }

    /**
     * @param RZ\Roadiz\Core\Entities\NodeTypeField $field
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildDeleteForm(NodeTypeField $field)
    {
        $builder = $this->getService('formFactory')
            ->createBuilder('form')
            ->add('nodeTypeFieldId', 'hidden', [
                'data' => $field->getId(),
                'constraints' => [
                    new NotBlank()
                ]
            ]);

        return $builder->getForm();
    }
}
