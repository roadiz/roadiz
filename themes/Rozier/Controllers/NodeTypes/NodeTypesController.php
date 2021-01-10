<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers\NodeTypes;

use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use RZ\Roadiz\Core\Handlers\NodeTypeHandler;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Themes\Rozier\Forms\NodeTypeType;
use Themes\Rozier\RozierApp;
use Themes\Rozier\Utils\SessionListFilters;

/**
 * @package Themes\Rozier\Controllers\NodeTypes
 */
class NodeTypesController extends RozierApp
{
    /**
     * List every node-types.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_NODETYPES');
        /*
         * Manage get request to filter list
         */
        $listManager = $this->createEntityListManager(
            NodeType::class,
            [],
            ['name' => 'ASC']
        );
        $listManager->setDisplayingNotPublishedNodes(true);

        /*
         * Stored in session
         */
        $sessionListFilter = new SessionListFilters('node_types_item_per_page');
        $sessionListFilter->handleItemPerPage($request, $listManager);

        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['node_types'] = $listManager->getEntities();

        return $this->render('node-types/list.html.twig', $this->assignation);
    }

    /**
     * Return an edition form for requested node-type.
     *
     * @param Request $request
     * @param int     $nodeTypeId
     *
     * @return Response
     */
    public function editAction(Request $request, $nodeTypeId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_NODETYPES');

        /** @var NodeType $nodeType */
        $nodeType = $this->get('em')
                         ->find(NodeType::class, (int) $nodeTypeId);

        if (null !== $nodeType) {
            $this->assignation['nodeType'] = $nodeType;

            $form = $this->createForm(NodeTypeType::class, $nodeType, [
                'em' => $this->get('em'),
                'name' => $nodeType->getName(),
            ]);

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $this->get('em')->flush();
                    /** @var NodeTypeHandler $handler */
                    $handler = $this->get('factory.handler')->getHandler($nodeType);
                    $handler->updateSchema();

                    $msg = $this->getTranslator()->trans('nodeType.%name%.updated', ['%name%' => $nodeType->getName()]);
                    $this->publishConfirmMessage($request, $msg);
                } catch (EntityAlreadyExistsException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                }
                /*
                 * Redirect to update schema page
                 */
                return $this->redirect($this->generateUrl('nodeTypesSchemaUpdate'));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('node-types/edit.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Return an creation form for requested node-type.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function addAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_NODETYPES');

        $nodeType = new NodeType();

        if (null !== $nodeType) {
            $this->assignation['nodeType'] = $nodeType;

            /*
             * form
             */
            $form = $this->createForm(NodeTypeType::class, $nodeType, [
                'em' => $this->get('em'),
            ]);

            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $this->get('em')->persist($nodeType);
                    $this->get('em')->flush();
                    /** @var NodeTypeHandler $handler */
                    $handler = $this->get('factory.handler')->getHandler($nodeType);
                    $handler->updateSchema();

                    $msg = $this->getTranslator()->trans('nodeType.%name%.created', ['%name%' => $nodeType->getName()]);
                    $this->publishConfirmMessage($request, $msg);

                    /*
                     * Redirect to update schema page
                     */
                    return $this->redirect($this->generateUrl('nodeTypesSchemaUpdate'));
                } catch (EntityAlreadyExistsException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                    return $this->redirect($this->generateUrl(
                        'nodeTypesAddPage'
                    ));
                }
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('node-types/add.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Return an deletion form for requested node-type.
     *
     * @param Request $request
     * @param int     $nodeTypeId
     *
     * @return Response
     */
    public function deleteAction(Request $request, $nodeTypeId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_NODETYPES_DELETE');

        /** @var NodeType $nodeType */
        $nodeType = $this->get('em')
                         ->find(NodeType::class, (int) $nodeTypeId);

        if (null !== $nodeType) {
            $this->assignation['nodeType'] = $nodeType;

            $form = $this->buildDeleteForm($nodeType);

            $form->handleRequest($request);

            if ($form->isSubmitted() &&
                $form->isValid() &&
                $form->getData()['nodeTypeId'] == $nodeType->getId()) {
                /*
                 * Delete All node-type association and schema
                 */
                /** @var NodeTypeHandler $handler */
                $handler = $this->get('factory.handler')->getHandler($nodeType);
                $handler->deleteWithAssociations();

                $msg = $this->getTranslator()->trans('nodeType.%name%.deleted', ['%name%' => $nodeType->getName()]);
                $this->publishConfirmMessage($request, $msg);
                /*
                 * Redirect to update schema page
                 */
                return $this->redirect($this->generateUrl('nodeTypesSchemaUpdate'));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('node-types/delete.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * @param NodeType $nodeType
     *
     * @return FormInterface
     */
    private function buildDeleteForm(NodeType $nodeType)
    {
        $builder = $this->createFormBuilder()
                        ->add('nodeTypeId', HiddenType::class, [
                            'data' => $nodeType->getId(),
                            'constraints' => [
                                new NotNull(),
                                new NotBlank(),
                            ],
                        ]);

        return $builder->getForm();
    }
}
