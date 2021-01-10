<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\Event;
use Themes\Rozier\RozierApp;
use Themes\Rozier\Utils\SessionListFilters;

abstract class AbstractAdminController extends RozierApp
{
    const ITEM_PER_PAGE = 20;

    /**
     * @return string
     */
    protected function getThemeDirectory(): string
    {
        return RozierApp::getThemeDir();
    }

    /**
     * @param Request $request
     * @return Response|null
     * @throws \Twig\Error\RuntimeError
     */
    public function defaultAction(Request $request)
    {
        $this->denyAccessUnlessGranted($this->getRequiredRole());

        $elm = $this->createEntityListManager(
            $this->getEntityClass(),
            $this->getDefaultCriteria(),
            $this->getDefaultOrder()
        );
        $elm->setDisplayingNotPublishedNodes(true);
        /*
         * Stored item per pages in session
         */
        $sessionListFilter = new SessionListFilters($this->getNamespace() . '_item_per_page');
        $sessionListFilter->handleItemPerPage($request, $elm);
        $elm->handle();

        $this->assignation['items'] = $elm->getEntities();
        $this->assignation['filters'] = $elm->getAssignation();

        return $this->render(
            $this->getTemplateFolder() . '/list.html.twig',
            $this->assignation
        );
    }

    /**
     * @param Request $request
     * @return RedirectResponse|Response|null
     * @throws \Twig\Error\RuntimeError
     */
    public function addAction(Request $request)
    {
        $this->denyAccessUnlessGranted($this->getRequiredRole());

        $item = $this->createEmptyItem();
        $form = $this->createForm($this->getFormType(), $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event = $this->createCreateEvent($item);
            if (null !== $event) {
                $this->get('dispatcher')->dispatch($event);
            }
            $this->get('em')->persist($item);
            $this->get('em')->flush();

            $msg = $this->getTranslator()->trans(
                '%namespace%.%item%.was_created',
                [
                    '%item%' => $this->getEntityName($item),
                    '%namespace%' => $this->getTranslator()->trans($this->getNamespace())
                ]
            );
            $this->publishConfirmMessage($request, $msg);

            return $this->redirect($this->get('urlGenerator')->generate(
                $this->getEditRouteName(),
                [
                    'id' => $item->getId()
                ]
            ));
        }

        $this->assignation['form'] = $form->createView();
        $this->assignation['item'] = $item;

        return $this->render(
            $this->getTemplateFolder() . '/add.html.twig',
            $this->assignation
        );
    }

    /**
     * @param Request $request
     * @param int $id
     * @return RedirectResponse|Response|null
     * @throws \Twig\Error\RuntimeError
     */
    public function editAction(Request $request, int $id)
    {
        $this->denyAccessUnlessGranted($this->getRequiredRole());

        /** @var mixed|object|null $item */
        $item = $this->get('em')->find($this->getEntityClass(), $id);

        if (null === $item || !($item instanceof AbstractEntity)) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm($this->getFormType(), $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event = $this->createUpdateEvent($item);
            if (null !== $event) {
                $this->get('dispatcher')->dispatch($event);
            }
            $this->get('em')->flush();

            $msg = $this->getTranslator()->trans(
                '%namespace%.%item%.was_updated',
                [
                    '%item%' => $this->getEntityName($item),
                    '%namespace%' => $this->getTranslator()->trans($this->getNamespace())
                ]
            );
            $this->publishConfirmMessage($request, $msg);

            return $this->redirect($this->get('urlGenerator')->generate(
                $this->getEditRouteName(),
                [
                    'id' => $item->getId()
                ]
            ));
        }

        $this->assignation['form'] = $form->createView();
        $this->assignation['item'] = $item;

        return $this->render(
            $this->getTemplateFolder() . '/edit.html.twig',
            $this->assignation
        );
    }

    /**
     * @param Request $request
     * @param int $id
     * @return RedirectResponse|Response|null
     * @throws \Twig\Error\RuntimeError
     */
    public function deleteAction(Request $request, int $id)
    {
        $this->denyAccessUnlessGranted($this->getRequiredRole());

        /** @var mixed|object|null $item */
        $item = $this->get('em')->find($this->getEntityClass(), $id);

        if (null === $item) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(FormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event = $this->createDeleteEvent($item);
            if (null !== $event) {
                $this->get('dispatcher')->dispatch($event);
            }
            $this->get('em')->remove($item);
            $this->get('em')->flush();

            $msg = $this->getTranslator()->trans(
                '%namespace%.%item%.was_deleted',
                [
                    '%item%' => $this->getEntityName($item),
                    '%namespace%' => $this->getTranslator()->trans($this->getNamespace())
                ]
            );
            $this->publishConfirmMessage($request, $msg);

            return $this->redirect($this->get('urlGenerator')->generate($this->getDefaultRouteName()));
        }

        $this->assignation['form'] = $form->createView();
        $this->assignation['item'] = $item;

        return $this->render(
            $this->getTemplateFolder() . '/delete.html.twig',
            $this->assignation
        );
    }

    /**
     * @param AbstractEntity $item
     * @return bool
     */
    abstract protected function supports(AbstractEntity $item): bool;

    /**
     * @return string
     */
    abstract protected function getNamespace(): string;

    /**
     * @return AbstractEntity
     */
    abstract protected function createEmptyItem(): AbstractEntity;

    /**
     * @return string
     */
    abstract protected function getTemplateFolder(): string;

    /**
     * @return string
     */
    abstract protected function getRequiredRole(): string;

    /**
     * @return string
     */
    abstract protected function getEntityClass(): string;

    /**
     * @return string
     */
    abstract protected function getFormType(): string;

    /**
     * @return array
     */
    abstract protected function getDefaultCriteria(): array;

    /**
     * @return array
     */
    abstract protected function getDefaultOrder(): array;

    /**
     * @return string
     */
    abstract protected function getDefaultRouteName(): string;

    /**
     * @return string
     */
    abstract protected function getEditRouteName(): string;

    /**
     * @param AbstractEntity $item
     * @return Event|null
     */
    abstract protected function createCreateEvent(AbstractEntity $item): ?Event;

    /**
     * @param AbstractEntity $item
     * @return Event|null
     */
    abstract protected function createUpdateEvent(AbstractEntity $item): ?Event;

    /**
     * @param AbstractEntity $item
     * @return Event|null
     */
    abstract protected function createDeleteEvent(AbstractEntity $item): ?Event;

    /**
     * @param AbstractEntity $item
     * @return string
     */
    abstract protected function getEntityName(AbstractEntity $item): string;
}
