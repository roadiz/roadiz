<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use RZ\Roadiz\Core\AbstractEntities\PersistableInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\UnicodeString;
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
     * @return string
     */
    protected function getTemplateNamespace(): string
    {
        return '';
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
            $this->assignation,
            null,
            $this->getTemplateNamespace()
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

        $item = $this->createEmptyItem($request);
        $form = $this->createForm($this->getFormType(), $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /*
             * Events are dispatched before entity manager is flushed
             * to be able to throw exceptions before it is persisted.
             */
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

            return $this->getPostSubmitResponse($item, $request);
        }

        $this->assignation['form'] = $form->createView();
        $this->assignation['item'] = $item;

        return $this->render(
            $this->getTemplateFolder() . '/add.html.twig',
            $this->assignation,
            null,
            $this->getTemplateNamespace()
        );
    }

    /**
     * @param Request $request
     * @param int|string $id Numeric ID or UUID
     * @return RedirectResponse|Response|null
     * @throws \Twig\Error\RuntimeError
     */
    public function editAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted($this->getRequiredRole());

        /** @var mixed|object|null $item */
        $item = $this->get('em')->find($this->getEntityClass(), $id);

        if (null === $item || !($item instanceof PersistableInterface)) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessItemGranted($item);

        $form = $this->createForm($this->getFormType(), $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /*
             * Events are dispatched before entity manager is flushed
             * to be able to throw exceptions before it is persisted.
             */
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

            return $this->getPostSubmitResponse($item, $request);
        }

        $this->assignation['form'] = $form->createView();
        $this->assignation['item'] = $item;

        return $this->render(
            $this->getTemplateFolder() . '/edit.html.twig',
            $this->assignation,
            null,
            $this->getTemplateNamespace()
        );
    }

    /**
     * @return JsonResponse
     */
    public function exportAction()
    {
        $this->denyAccessUnlessGranted($this->getRequiredRole());

        $items = $this->get('em')->getRepository($this->getEntityClass())->findAll();
        /** @var Serializer $serializer */
        $serializer = $this->get('serializer');

        return new JsonResponse(
            $serializer->serialize(
                $items,
                'json',
                SerializationContext::create()->setGroups([$this->getNamespace()])
            ),
            JsonResponse::HTTP_OK,
            [
                'Content-Disposition' => sprintf(
                    'attachment; filename="%s_%s.json"',
                    $this->getNamespace(),
                    (new \DateTime())->format('YmdHi')
                ),
            ],
            true
        );
    }

    /**
     * @param Request $request
     * @param int|string $id Numeric ID or UUID
     * @return RedirectResponse|Response|null
     * @throws \Twig\Error\RuntimeError
     */
    public function deleteAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted($this->getRequiredDeletionRole());

        /** @var mixed|object|null $item */
        $item = $this->get('em')->find($this->getEntityClass(), $id);

        if (null === $item) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessItemGranted($item);

        $form = $this->createForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /*
             * Events are dispatched before entity manager is flushed
             * to be able to throw exceptions before it is persisted.
             */
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

            return $this->getPostDeleteResponse($item);
        }

        $this->assignation['form'] = $form->createView();
        $this->assignation['item'] = $item;

        return $this->render(
            $this->getTemplateFolder() . '/delete.html.twig',
            $this->assignation,
            null,
            $this->getTemplateNamespace()
        );
    }

    /**
     * @param PersistableInterface $item
     * @return bool
     */
    abstract protected function supports(PersistableInterface $item): bool;

    /**
     * @return string Namespace is used for composing messages and translations.
     */
    abstract protected function getNamespace(): string;

    /**
     * @param Request $request
     * @return PersistableInterface
     */
    abstract protected function createEmptyItem(Request $request): PersistableInterface;

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
    protected function getRequiredDeletionRole(): string
    {
        return $this->getRequiredRole();
    }

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
    protected function getDefaultCriteria(): array
    {
        return [];
    }

    /**
     * @return array
     */
    protected function getDefaultOrder(): array
    {
        return [];
    }

    /**
     * @return string
     */
    abstract protected function getDefaultRouteName(): string;

    /**
     * @return string
     */
    abstract protected function getEditRouteName(): string;

    /**
     * @param PersistableInterface $item
     * @param Request|null $request
     * @return Response
     */
    protected function getPostSubmitResponse(PersistableInterface $item, ?Request $request = null): Response
    {
        /*
         * Force redirect to avoid resending form when refreshing page
         */
        if (null !== $request && $request->query->has('referer') &&
            (new UnicodeString($request->query->get('referer')))->startsWith('/')) {
            return $this->redirect($request->query->get('referer'));
        }

        return $this->redirect($this->get('urlGenerator')->generate(
            $this->getEditRouteName(),
            [
                'id' => $item->getId()
            ]
        ));
    }

    /**
     * @param PersistableInterface $item
     * @return Response
     */
    protected function getPostDeleteResponse(PersistableInterface $item): Response
    {
        return $this->redirect($this->get('urlGenerator')->generate($this->getDefaultRouteName()));
    }

    /**
     * @param PersistableInterface $item
     * @return Event|null
     */
    protected function createCreateEvent(PersistableInterface $item): ?Event
    {
        return null;
    }

    /**
     * @param PersistableInterface $item
     * @return Event|null
     */
    protected function createUpdateEvent(PersistableInterface $item): ?Event
    {
        return null;
    }

    /**
     * @param PersistableInterface $item
     * @return Event|null
     */
    protected function createDeleteEvent(PersistableInterface $item): ?Event
    {
        return null;
    }

    /**
     * @param PersistableInterface $item
     * @return string
     */
    abstract protected function getEntityName(PersistableInterface $item): string;

    /**
     * @param PersistableInterface $item
     */
    protected function denyAccessUnlessItemGranted(PersistableInterface $item): void
    {
        // Do nothing
    }
}
