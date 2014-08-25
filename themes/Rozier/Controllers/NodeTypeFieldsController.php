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
use Themes\Rozier\RozierApp;

use RZ\Renzo\Core\Exceptions\EntityAlreadyExistsException;

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
        $nodeType = $this->getKernel()->em()
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
        $field = $this->getKernel()->em()
            ->find('RZ\Renzo\Core\Entities\NodeTypeField', (int) $nodeTypeFieldId);

        if ($field !== null) {

            $this->assignation['nodeType'] = $field->getNodeType();
            $this->assignation['field'] = $field;
            $form = $this->buildEditForm($field);
            $form->handleRequest();

            if ($form->isValid()) {
                $this->editNodeTypeField($form->getData(), $field);

                $msg = $this->getTranslator()->trans('nodeTypeField.updated', array('%name%'=>$field->getName()));
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getLogger()->info($msg);

                /*
                 * Redirect to update schema page
                 */
                $response = new RedirectResponse(
                    $this->getKernel()->getUrlGenerator()->generate(
                        'nodeTypesFieldSchemaUpdate',
                        array(
                            'nodeTypeId' => $field->getNodeType()->getId(),
                            '_token' => $this->getKernel()->getCsrfProvider()->generateCsrfToken(
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
        $field = new NodeTypeField();
        $nodeType = $this->getKernel()->em()
            ->find('RZ\Renzo\Core\Entities\NodeType', (int) $nodeTypeId);

        if ($nodeType !== null &&
            $field !== null) {

            $this->assignation['nodeType'] = $nodeType;
            $this->assignation['field'] = $field;
            $form = $this->buildEditForm($field);
            $form->handleRequest();

            if ($form->isValid()) {
                $this->addNodeTypeField($form->getData(), $field, $nodeType);

                $msg = $this->getTranslator()->trans('nodeTypeField.created', array('%name%'=>$field->getName()));
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getLogger()->info($msg);


                /*
                 * Redirect to update schema page
                 */
                $response = new RedirectResponse(
                    $this->getKernel()->getUrlGenerator()->generate(
                        'nodeTypesFieldSchemaUpdate',
                        array(
                            'nodeTypeId' => $nodeTypeId,
                            '_token' => $this->getKernel()->getCsrfProvider()->generateCsrfToken(
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
        $field = $this->getKernel()->em()
            ->find('RZ\Renzo\Core\Entities\NodeTypeField', (int) $nodeTypeFieldId);

        if ($field !== null) {
            $this->assignation['field'] = $field;
            $form = $this->buildDeleteForm($field);
            $form->handleRequest();

            if ($form->isValid() &&
                $form->getData()['nodeTypeFieldId'] == $field->getId()) {

                $nodeTypeId = $field->getNodeType()->getId();

                $this->getKernel()->em()->remove($field);
                $this->getKernel()->em()->flush();

                /*
                 * Update Database
                 */
                $nodeType = $this->getKernel()->em()
                    ->find('RZ\Renzo\Core\Entities\NodeType', (int) $nodeTypeId);

                $nodeType->getHandler()->updateSchema();

                $msg = $this->getTranslator()->trans(
                    'nodeTypeField.deleted',
                    array('%name%'=>$field->getName())
                );
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getLogger()->info($msg);

                /*
                 * Redirect to update schema page
                 */
                $response = new RedirectResponse(
                    $this->getKernel()->getUrlGenerator()->generate(
                        'nodeTypesFieldSchemaUpdate',
                        array(
                            'nodeTypeId' => $nodeTypeId,
                            '_token' => $this->getKernel()->getCsrfProvider()->generateCsrfToken(
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

        $this->getKernel()->em()->flush();
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
    )
    {
        foreach ($data as $key => $value) {
            $setter = 'set'.ucwords($key);
            $field->$setter($value);
        }

        $field->setNodeType($nodeType);

        $this->getKernel()->em()->persist($field);
        $this->getKernel()->em()->flush();

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
        );
        $builder = $this->getFormFactory()
                    ->createBuilder('form', $defaults)
                    ->add('name', 'text', array(
                        'constraints' => array(
                            new NotBlank()
                        )
                    ))
                    ->add('label', 'text', array(
                        'constraints' => array(
                            new NotBlank()
                        )
                    ))
                    ->add('type', 'choice', array(
                        'required' => true,
                        'choices' => NodeTypeField::$typeToHuman
                    ))
                    ->add('description', 'text', array('required' => false))
                    ->add('visible', 'checkbox', array('required' => false))
                    ->add('indexed', 'checkbox', array('required' => false));

        return $builder->getForm();
    }

    /**
     * @param RZ\Renzo\Core\Entities\NodeTypeField $field
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildDeleteForm(NodeTypeField $field)
    {
        $builder = $this->getFormFactory()
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