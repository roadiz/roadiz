<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file NodeTypeFieldsController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\Entities\NodeTypeField;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\ListManagers\EntityListManager;
use RZ\Renzo\Core\Exceptions\EntityAlreadyExistsException;
use Themes\Rozier\RozierApp;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use \Symfony\Component\Form\Form;
use \Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use \Symfony\Component\Validator\Constraints\NotBlank;
use \Symfony\Component\Validator\Constraints\Type;

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
            ->find('RZ\Renzo\Core\Entities\NodeType', (int) $nodeTypeId);

        if ($nodeType !== null) {
            $fields = $nodeType->getFields();

            $this->assignation['nodeType'] = $nodeType;
            $this->assignation['fields'] = $fields;

            return new Response(
                $this->getTwig()->render('node-type-fields/list.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
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
            ->find('RZ\Renzo\Core\Entities\NodeTypeField', (int) $nodeTypeFieldId);

        if ($field !== null) {

            $this->assignation['nodeType'] = $field->getNodeType();
            $this->assignation['field'] = $field;
            $form = $this->buildEditForm($field);
            $form->handleRequest();

            if ($form->isValid()) {
                $this->editNodeTypeField($form->getData(), $field);

                $msg = $this->getTranslator()->trans('nodeTypeField.%name%.updated', array('%name%'=>$field->getName()));
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getService('logger')->info($msg);

                /*
                 * Redirect to update schema page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate(
                        'nodeTypesFieldSchemaUpdate',
                        array(
                            'nodeTypeId' => $field->getNodeType()->getId(),
                            '_token' => $this->getService('csrfProvider')->generateCsrfToken(
                                static::SCHEMA_TOKEN_INTENTION
                            )
                        )
                    )
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('node-type-fields/edit.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
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
            ->find('RZ\Renzo\Core\Entities\NodeType', (int) $nodeTypeId);

        if ($nodeType !== null &&
            $field !== null) {

            $this->assignation['nodeType'] = $nodeType;
            $this->assignation['field'] = $field;
            $form = $this->buildEditForm($field);
            $form->handleRequest();

            if ($form->isValid()) {
                $this->addNodeTypeField($form->getData(), $field, $nodeType);

                $msg = $this->getTranslator()->trans('nodeTypeField.%name%.created', array('%name%'=>$field->getName()));
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getService('logger')->info($msg);


                /*
                 * Redirect to update schema page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate(
                        'nodeTypesFieldSchemaUpdate',
                        array(
                            'nodeTypeId' => $nodeTypeId,
                            '_token' => $this->getService('csrfProvider')->generateCsrfToken(
                                static::SCHEMA_TOKEN_INTENTION
                            )
                        )
                    )
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('node-type-fields/add.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
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
            ->find('RZ\Renzo\Core\Entities\NodeTypeField', (int) $nodeTypeFieldId);

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
                    ->find('RZ\Renzo\Core\Entities\NodeType', (int) $nodeTypeId);

                $nodeType->getHandler()->updateSchema();

                $msg = $this->getTranslator()->trans(
                    'nodeTypeField.%name%.deleted',
                    array('%name%'=>$field->getName())
                );
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getService('logger')->info($msg);

                /*
                 * Redirect to update schema page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate(
                        'nodeTypesFieldSchemaUpdate',
                        array(
                            'nodeTypeId' => $nodeTypeId,
                            '_token' => $this->getService('csrfProvider')->generateCsrfToken(
                                static::SCHEMA_TOKEN_INTENTION
                            )
                        )
                    )
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('node-type-fields/delete.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * @param array                                $data
     * @param RZ\Renzo\Core\Entities\NodeTypeField $field
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
     * @param RZ\Renzo\Core\Entities\NodeTypeField $field
     * @param RZ\Renzo\Core\Entities\NodeType      $nodeType
     */
    private function addNodeTypeField(
        $data,
        NodeTypeField $field,
        NodeType $nodeType
    ) {
        foreach ($data as $key => $value) {
            $setter = 'set'.ucwords($key);
            $field->$setter($value);
        }

        $field->setNodeType($nodeType);

        $this->getService('em')->persist($field);
        $this->getService('em')->flush();

        $nodeType->getHandler()->updateSchema();
    }

    /**
     * @param RZ\Renzo\Core\Entities\NodeTypeField $field
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildEditForm(NodeTypeField $field)
    {
        $defaults = array(
            'name' =>           $field->getName(),
            'label' =>          $field->getLabel(),
            'type' =>           $field->getType(),
            'description' =>    $field->getDescription(),
            'visible' =>        $field->isVisible(),
            'indexed' =>        $field->isIndexed(),
            'defaultValues' => $field->getDefaultValues(),
        );
        $builder = $this->getService('formFactory')
                    ->createBuilder('form', $defaults)
                    ->add('name', 'text', array(
                        'label' => $this->getTranslator()->trans('name'),
                        'constraints' => array(
                            new NotBlank()
                        )
                    ))
                    ->add('label', 'text', array(
                        'label' => $this->getTranslator()->trans('label'),
                        'constraints' => array(
                            new NotBlank()
                        )
                    ))
                    ->add('type', 'choice', array(
                        'label' => $this->getTranslator()->trans('type'),
                        'required' => true,
                        'choices' => NodeTypeField::$typeToHuman
                    ))
                    ->add('description', 'text', array(
                        'label' => $this->getTranslator()->trans('description'),
                        'required' => false
                    ))
                    ->add('visible', 'checkbox', array(
                        'label' => $this->getTranslator()->trans('visible'),
                        'required' => false
                    ))
                    ->add('indexed', 'checkbox', array(
                        'label' => $this->getTranslator()->trans('indexed'),
                        'required' => false
                    ))
                    ->add(
                        'defaultValues',
                        'text',
                        array(
                            'label' => $this->getTranslator()->trans('defaultValues'),
                            'required' => false,
                            'attr' => array(
                                'placeholder' => $this->getTranslator()->trans('enter_values_comma_separated')
                            )
                        )
                    );

        return $builder->getForm();
    }

    /**
     * @param RZ\Renzo\Core\Entities\NodeTypeField $field
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildDeleteForm(NodeTypeField $field)
    {
        $builder = $this->getService('formFactory')
            ->createBuilder('form')
            ->add('nodeTypeFieldId', 'hidden', array(
                'data' => $field->getId(),
                'constraints' => array(
                    new NotBlank()
                )
            ));

        return $builder->getForm();
    }
}
