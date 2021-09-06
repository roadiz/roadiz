<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Handlers\NodeTypeHandler;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Themes\Rozier\Forms\NodeTypeFieldType;
use Themes\Rozier\RozierApp;

/**
 * @package Themes\Rozier\Controllers
 */
class NodeTypeFieldsController extends RozierApp
{
    /**
     * @param Request $request
     * @param int $nodeTypeId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(Request $request, int $nodeTypeId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_NODETYPES');

        /** @var NodeType|null $nodeType */
        $nodeType = $this->get('em')->find(NodeType::class, $nodeTypeId);

        if ($nodeType !== null) {
            $fields = $nodeType->getFields();

            $this->assignation['nodeType'] = $nodeType;
            $this->assignation['fields'] = $fields;

            return $this->render('node-type-fields/list.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * @param Request $request
     * @param int     $nodeTypeFieldId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, int $nodeTypeFieldId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_NODETYPES');

        /** @var NodeTypeField|null $field */
        $field = $this->get('em')->find(NodeTypeField::class, $nodeTypeFieldId);

        if ($field !== null) {
            $this->assignation['nodeType'] = $field->getNodeType();
            $this->assignation['field'] = $field;

            $form = $this->createForm(NodeTypeFieldType::class, $field, [
                'inheritance_type' => $this->get('config')['inheritance']['type']
            ]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
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
     * @param Request $request
     * @param int     $nodeTypeId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request, int $nodeTypeId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_NODETYPES');

        $field = new NodeTypeField();
        /** @var NodeType|null $nodeType */
        $nodeType = $this->get('em')->find(NodeType::class, $nodeTypeId);

        if ($nodeType !== null) {
            $latestPosition = $this->get('em')
                                   ->getRepository(NodeTypeField::class)
                                   ->findLatestPositionInNodeType($nodeType);
            $field->setNodeType($nodeType);
            $field->setPosition($latestPosition + 1);
            $field->setType(NodeTypeField::STRING_T);

            $this->assignation['nodeType'] = $nodeType;
            $this->assignation['field'] = $field;

            $form = $this->createForm(NodeTypeFieldType::class, $field, [
                'inheritance_type' => $this->get('config')['inheritance']['type']
            ]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
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
                    $this->publishErrorMessage($request, $msg);
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
     * @param Request $request
     * @param int     $nodeTypeFieldId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, int $nodeTypeFieldId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_NODEFIELDS_DELETE');

        /** @var NodeTypeField|null $field */
        $field = $this->get('em')->find(NodeTypeField::class, $nodeTypeFieldId);

        if ($field !== null) {
            $form = $this->createForm();
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $nodeTypeId = $field->getNodeType()->getId();
                $this->get('em')->remove($field);
                $this->get('em')->flush();

                /*
                 * Update Database
                 */
                /** @var NodeType|null $nodeType */
                $nodeType = $this->get('em')->find(NodeType::class, (int) $nodeTypeId);

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

            $this->assignation['field'] = $field;
            $this->assignation['form'] = $form->createView();

            return $this->render('node-type-fields/delete.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * @param NodeTypeField $field
     *
     * @return FormInterface
     */
    private function buildDeleteForm(NodeTypeField $field)
    {
        $builder = $this->createFormBuilder()
                        ->add('nodeTypeFieldId', HiddenType::class, [
                            'data' => $field->getId(),
                            'constraints' => [
                                new NotNull(),
                                new NotBlank(),
                            ],
                        ]);

        return $builder->getForm();
    }
}
