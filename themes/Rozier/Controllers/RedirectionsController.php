<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Entities\Redirection;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Themes\Rozier\Forms\RedirectionType;
use Themes\Rozier\RozierApp;
use Themes\Rozier\Utils\SessionListFilters;

/**
 * @package Themes\Rozier\Controllers
 */
class RedirectionsController extends RozierApp
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Twig\Error\RuntimeError
     */
    public function indexAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_REDIRECTIONS');

        $listManager = $this->createEntityListManager(
            Redirection::class,
            [],
            ['query' => 'ASC']
        );
        $listManager->setDisplayingNotPublishedNodes(true);
        /*
         * Stored in session
         */
        $sessionListFilter = new SessionListFilters('redirections_item_per_page');
        $sessionListFilter->handleItemPerPage($request, $listManager);
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['redirections'] = $listManager->getEntities();

        return $this->render('redirections/list.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     * @param int $redirectionId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $redirectionId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_REDIRECTIONS');

        /** @var Redirection|null $redirection */
        $redirection = $this->get('em')->find(Redirection::class, $redirectionId);

        if (null === $redirection) {
            throw new ResourceNotFoundException();
        }

        $form = $this->createForm(RedirectionType::class, $redirection, [
            'entityManager' => $this->get('em')
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get('em')->flush();

            return $this->redirect($this->generateUrl('redirectionsEditPage', [
                'redirectionId' => $redirectionId
            ]));
        }

        $this->assignation['form'] = $form->createView();
        $this->assignation['redirection'] = $redirection;

        return $this->render('redirections/edit.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     * @param integer $redirectionId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $redirectionId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_REDIRECTIONS');

        /** @var Redirection|null $redirection */
        $redirection = $this->get('em')->find(Redirection::class, $redirectionId);

        if (null === $redirection) {
            throw new ResourceNotFoundException();
        }

        $form = $this->createForm(FormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get('em')->remove($redirection);
            $this->get('em')->flush();

            return $this->redirect($this->generateUrl('redirectionsHomePage'));
        }
        $this->assignation['form'] = $form->createView();
        $this->assignation['redirection'] = $redirection;

        return $this->render('redirections/delete.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_REDIRECTIONS');

        $redirection = new Redirection();
        $form = $this->createForm(RedirectionType::class, $redirection, [
            'entityManager' => $this->get('em')
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get('em')->persist($redirection);
            $this->get('em')->flush();

            return $this->redirect($this->generateUrl('redirectionsHomePage'));
        }

        $this->assignation['form'] = $form->createView();

        return $this->render('redirections/add.html.twig', $this->assignation);
    }
}
