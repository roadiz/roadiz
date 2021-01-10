<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers\Attributes;

use RZ\Roadiz\Attribute\Form\AttributeGroupType;
use RZ\Roadiz\Core\Entities\AttributeGroup;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\UnicodeString;
use Themes\Rozier\RozierApp;

/**
 * @package Themes\Rozier\Controllers\Attributes
 */
class AttributeGroupController extends RozierApp
{
    /**
     * @param Request $request
     *
     * @return Response
     */
    public function listAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_ATTRIBUTES');

        $listManager = $this->createEntityListManager(
            AttributeGroup::class,
            [],
            ['canonicalName' => 'ASC']
        );
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['items'] = $listManager->getEntities();

        return $this->render('attributes/groups/list.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function addAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_ATTRIBUTES');

        $item = new AttributeGroup();

        $form = $this->createForm(AttributeGroupType::class, $item, [
            'entityManager' => $this->get('em'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->get('em')->persist($item);
                $this->get('em')->flush();

                $msg = $this->getTranslator()->trans(
                    'attribute_group.%name%.created',
                    ['%name%' => $item->getName()]
                );
                $this->publishConfirmMessage($request, $msg);
            } catch (\RuntimeException $e) {
                $this->publishErrorMessage($request, $e->getMessage());
            }

            /*
             * Force redirect to avoid resending form when refreshing page
             */
            if ($request->query->has('referer') &&
                (new UnicodeString($request->query->get('referer')))->startsWith('/')) {
                return $this->redirect($request->query->get('referer'));
            }
            return $this->redirect($this->generateUrl('attributeGroupsEditPage', ['id' => $item->getId()]));
        }

        $this->assignation['form'] = $form->createView();

        return $this->render('attributes/groups/add.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     * @param int     $id
     *
     * @return Response
     */
    public function editAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_ATTRIBUTES');

        /** @var AttributeGroup $item */
        $item = $this->get('em')->find(AttributeGroup::class, (int) $id);

        if ($item === null) {
            throw $this->createNotFoundException('AttributeGroup does not exist.');
        }

        $form = $this->createForm(AttributeGroupType::class, $item, [
            'entityManager' => $this->get('em'),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->get('em')->flush();
                $msg = $this->getTranslator()->trans(
                    'attribute_group.%name%.updated',
                    ['%name%' => $item->getName()]
                );
                $this->publishConfirmMessage($request, $msg);
            } catch (\RuntimeException $e) {
                $this->publishErrorMessage($request, $e->getMessage());
            }
            return $this->redirect($this->generateUrl('attributeGroupsEditPage', ['id' => $item->getId()]));
        }

        $this->assignation['item'] = $item;
        $this->assignation['form'] = $form->createView();

        return $this->render('attributes/groups/edit.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     * @param int     $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function deleteAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_ATTRIBUTES_DELETE');

        /** @var AttributeGroup $item */
        $item = $this->get('em')->find(AttributeGroup::class, (int) $id);

        if ($item === null) {
            throw $this->createNotFoundException('AttributeGroup does not exist.');
        }

        $form = $this->createForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->get('em')->remove($item);
                $this->get('em')->flush();

                $msg = $this->getTranslator()->trans(
                    'attribute_group.%name%.deleted',
                    ['%name%' => $item->getName()]
                );
                $this->publishConfirmMessage($request, $msg);
            } catch (\RuntimeException $e) {
                $this->publishErrorMessage($request, $e->getMessage());
            }

            return $this->redirect($this->generateUrl('attributeGroupsHomePage'));
        }

        $this->assignation['form'] = $form->createView();
        $this->assignation['item'] = $item;

        return $this->render('attributes/groups/delete.html.twig', $this->assignation);
    }
}
