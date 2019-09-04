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
 * @file TagsController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers\Tags;

use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\TagTranslation;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Events\FilterTagEvent;
use RZ\Roadiz\Core\Events\TagEvents;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use RZ\Roadiz\Core\Handlers\TagHandler;
use RZ\Roadiz\Core\Repositories\TranslationRepository;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Validator\Constraints\NotBlank;
use Themes\Rozier\Forms\TagTranslationType;
use Themes\Rozier\Forms\TagType;
use Themes\Rozier\RozierApp;
use Themes\Rozier\Traits\VersionedControllerTrait;
use Themes\Rozier\Widgets\TagTreeWidget;
use Twig_Error_Runtime;

/**
 * {@inheritdoc}
 */
class TagsController extends RozierApp
{
    use VersionedControllerTrait;

    /**
     * List every tags.
     *
     * @param Request $request
     *
     * @return Response
     * @throws Twig_Error_Runtime
     */
    public function indexAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_TAGS');

        /*
         * Manage get request to filter list
         */
        $listManager = $this->createEntityListManager(
            Tag::class
        );
        $listManager->setDisplayingNotPublishedNodes(true);
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['tags'] = $listManager->getEntities();

        if ($this->isGranted('ROLE_ACCESS_TAGS_DELETE')) {
            /*
             * Handle bulk delete form
             */
            $deleteTagsForm = $this->buildBulkDeleteForm($request->getRequestUri());
            $this->assignation['deleteTagsForm'] = $deleteTagsForm->createView();
        }

        return $this->render('tags/list.html.twig', $this->assignation);
    }

    /**
     * Return an edition form for current translated tag.
     *
     * @param Request        $request
     * @param integer        $tagId
     * @param integer | null $translationId
     *
     * @return Response
     * @throws Twig_Error_Runtime
     */
    public function editTranslatedAction(Request $request, $tagId, $translationId = null)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_TAGS');

        /** @var TranslationRepository $translationRepository */
        $translationRepository = $this->get('em')->getRepository(Translation::class);

        if (null === $translationId) {
            /** @var Translation $translation */
            $translation = $this->get('defaultTranslation');
        } else {
            /** @var Translation $translation */
            $translation = $this->get('em')->find(Translation::class, (int) $translationId);
        }

        if (null !== $translation) {
            /*
             * Here we need to directly select tagTranslation
             * if not doctrine will grab a cache tag because of TagTreeWidget
             * that is initialized before calling route method.
             */
            /** @var Tag $tag */
            $tag = $this->get('em')->find(Tag::class, (int) $tagId);

            /** @var TagTranslation|null $tagTranslation */
            $tagTranslation = $this->get('em')->getRepository(TagTranslation::class)
                ->findOneBy(['translation' => $translation, 'tag' => $tag]);

            if (null === $tagTranslation && null === $tag) {
                throw new ResourceNotFoundException();
            }

            if (null === $tagTranslation && null !== $tag) {
                /*
                 * If translation does not exist, we created it.
                 */
                $this->get('em')->refresh($tag);
                $baseTranslation = $tag->getTranslatedTags()->first();
                $tagTranslation = new TagTranslation($tag, $translation);
                if (false !== $baseTranslation) {
                    $tagTranslation->setName($baseTranslation->getName());
                } else {
                    $tagTranslation->setName('tag_' . $tag->getId());
                }
                $this->get('em')->persist($tagTranslation);
                $this->get('em')->flush();
            }

            /**
             * Versioning
             */
            if ($this->isGranted('ROLE_ACCESS_VERSIONS')) {
                if (null !== $response = $this->handleVersions($request, $tagTranslation)) {
                    return $response;
                }
            }

            $form = $this->createForm(TagTranslationType::class, $tagTranslation, [
                'em' => $this->get('em'),
                'tagName' => $tag->getTagName(),
                'disabled' => $this->isReadOnly,
            ]);
            $form->handleRequest($request);

            if ($form->isValid()) {
                /*
                 * Update tag slug if not locked
                 * only from default translation.
                 */
                if (!$tag->isLocked() &&
                    $translation->isDefaultTranslation()) {
                    $tag->setTagName($tagTranslation->getName());
                }
                $this->get('em')->flush();
                /*
                 * Dispatch event
                 */
                $this->get('dispatcher')->dispatch(
                    TagEvents::TAG_UPDATED,
                    new FilterTagEvent($tag)
                );

                $msg = $this->getTranslator()->trans('tag.%name%.updated', [
                    '%name%' => $tagTranslation->getName(),
                ]);
                $this->publishConfirmMessage($request, $msg);

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->getPostUpdateRedirection($tagTranslation);
            }

            $this->assignation['tag'] = $tag;
            $this->assignation['translation'] = $translation;
            $this->assignation['translatedTag'] = $tagTranslation;
            $this->assignation['available_translations'] = $translationRepository->findAllAvailable();
            $this->assignation['translations'] = $translationRepository->findAvailableTranslationsForTag($tag);
            $this->assignation['form'] = $form->createView();
            $this->assignation['readOnly'] = $this->isReadOnly;

            return $this->render('tags/edit.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws Twig_Error_Runtime
     */
    public function bulkDeleteAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_TAGS_DELETE');

        if (!empty($request->get('deleteForm')['tagsIds'])) {
            $tagsIds = trim($request->get('deleteForm')['tagsIds']);
            $tagsIds = explode(',', $tagsIds);
            array_filter($tagsIds);

            $tags = $this->get('em')
                ->getRepository(Tag::class)
                ->findBy([
                    'id' => $tagsIds,
                ]);

            if (count($tags) > 0) {
                $form = $this->buildBulkDeleteForm(
                    $request->get('deleteForm')['referer'],
                    $tagsIds
                );
                $form->handleRequest($request);

                if ($form->isValid()) {
                    $msg = $this->bulkDeleteTags($form->getData());

                    $this->publishConfirmMessage($request, $msg);

                    if (!empty($form->getData()['referer'])) {
                        return $this->redirect($form->getData()['referer']);
                    } else {
                        return $this->redirect($this->generateUrl('tagsHomePage'));
                    }
                }

                $this->assignation['tags'] = $tags;
                $this->assignation['form'] = $form->createView();

                if (!empty($request->get('deleteForm')['referer'])) {
                    $this->assignation['referer'] = $request->get('deleteForm')['referer'];
                }

                return $this->render('tags/bulkDelete.html.twig', $this->assignation);
            }
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Return an creation form for requested tag.
     *
     * @param Request $request
     *
     * @return Response
     * @throws Twig_Error_Runtime
     */
    public function addAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_TAGS');

        $tag = new Tag();

        $translation = $this->get('defaultTranslation');

        if ($tag !== null &&
            $translation !== null) {
            $this->assignation['tag'] = $tag;
            $form = $this->createForm(TagType::class, $tag, [
                'em' => $this->get('em'),
            ]);
            $form->handleRequest($request);

            if ($form->isValid()) {
                /*
                 * Get latest position to add tags after.
                 */
                $latestPosition = $this->get('em')
                    ->getRepository(Tag::class)
                    ->findLatestPositionInParent();
                $tag->setPosition($latestPosition + 1);

                $this->get('em')->persist($tag);
                $this->get('em')->flush();

                $translatedTag = new TagTranslation($tag, $translation);
                $this->get('em')->persist($translatedTag);
                $this->get('em')->flush();

                /*
                 * Dispatch event
                 */
                $event = new FilterTagEvent($tag);
                $this->get('dispatcher')->dispatch(TagEvents::TAG_CREATED, $event);

                $msg = $this->getTranslator()->trans('tag.%name%.created', ['%name%' => $tag->getTagName()]);
                $this->publishConfirmMessage($request, $msg);
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl('tagsHomePage'));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('tags/add.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Return a edition form for requested tag settings .
     *
     * @param Request $request
     * @param int     $tagId
     *
     * @return Response
     * @throws Twig_Error_Runtime
     */
    public function editSettingsAction(Request $request, $tagId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_TAGS');

        $translation = $this->get('defaultTranslation');

        /** @var Tag $tag */
        $tag = $this->get('em')
            ->find(Tag::class, (int) $tagId);

        if ($tag !== null) {
            $form = $this->createForm(TagType::class, $tag, [
                'em' => $this->get('em'),
                'tagName' => $tag->getTagName(),
            ]);

            $form->handleRequest($request);

            if ($form->isValid()) {
                $this->get('em')->flush();
                /*
                 * Dispatch event
                 */
                $event = new FilterTagEvent($tag);
                $this->get('dispatcher')->dispatch(TagEvents::TAG_UPDATED, $event);

                $msg = $this->getTranslator()->trans('tag.%name%.updated', ['%name%' => $tag->getTagName()]);
                $this->publishConfirmMessage($request, $msg);

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl(
                    'tagsSettingsPage',
                    ['tagId' => $tag->getId()]
                ));
            }

            $this->assignation['form'] = $form->createView();
            $this->assignation['tag'] = $tag;
            $this->assignation['translation'] = $translation;

            return $this->render('tags/settings.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * @param Request $request
     * @param int     $tagId
     * @param int     $translationId
     *
     * @return Response
     * @throws Twig_Error_Runtime
     */
    public function treeAction(Request $request, $tagId, $translationId = null)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_TAGS');

        $tag = $this->get('em')
            ->find(Tag::class, (int) $tagId);
        $this->get('em')->refresh($tag);

        if (null !== $translationId) {
            $translation = $this->get('em')
                ->getRepository(Translation::class)
                ->findOneBy(['id' => (int) $translationId]);
        } else {
            $translation = $this->get('defaultTranslation');
        }

        if (null !== $tag) {
            $widget = new TagTreeWidget($request, $this, $tag);
            $this->assignation['tag'] = $tag;
            $this->assignation['translation'] = $translation;
            $this->assignation['specificTagTree'] = $widget;
        }

        return $this->render('tags/tree.html.twig', $this->assignation);
    }

    /**
     * Return a deletion form for requested tag.
     *
     * @param Request $request
     * @param int     $tagId
     *
     * @return Response
     * @throws Twig_Error_Runtime
     */
    public function deleteAction(Request $request, $tagId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_TAGS_DELETE');

        /** @var Tag $tag */
        $tag = $this->get('em')
            ->find(Tag::class, (int) $tagId);

        if ($tag !== null &&
            !$tag->isLocked()) {
            $this->assignation['tag'] = $tag;

            $form = $this->buildDeleteForm($tag);
            $form->handleRequest($request);

            if ($form->isValid() &&
                $form->getData()['tagId'] == $tag->getId()) {
                /*
                 * Dispatch event
                 */
                $event = new FilterTagEvent($tag);
                $this->get('dispatcher')->dispatch(TagEvents::TAG_DELETED, $event);

                $this->get('em')->remove($tag);
                $this->get('em')->flush();

                $msg = $this->getTranslator()->trans('tag.%name%.deleted', ['%name%' => $tag->getTranslatedTags()->first()->getName()]);
                $this->publishConfirmMessage($request, $msg);

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl('tagsHomePage'));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('tags/delete.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Handle tag creation pages.
     *
     * @param Request $request
     * @param int     $tagId
     * @param int     $translationId
     *
     * @return Response
     * @throws Twig_Error_Runtime
     */
    public function addChildAction(Request $request, $tagId, $translationId = null)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_TAGS');

        $translation = $this->get('defaultTranslation');

        if ($translationId !== null) {
            $translation = $this->get('em')
                ->find(Translation::class, (int) $translationId);
        }
        $parentTag = $this->get('em')
            ->find(Tag::class, (int) $tagId);
        $tag = new Tag();
        $tag->setParent($parentTag);

        if ($translation !== null &&
            $parentTag !== null) {
            $form = $this->createForm(TagType::class, $tag, [
                'em' => $this->get('em'),
            ]);
            $form->handleRequest($request);

            if ($form->isValid()) {
                try {
                    /*
                     * Get latest position to add tags after.
                     */
                    $latestPosition = $this->get('em')
                        ->getRepository(Tag::class)
                        ->findLatestPositionInParent($parentTag);
                    $tag->setPosition($latestPosition + 1);

                    $this->get('em')->persist($tag);
                    $this->get('em')->flush();

                    $translatedTag = new TagTranslation($tag, $translation);
                    $this->get('em')->persist($translatedTag);
                    $this->get('em')->flush();
                    /*
                     * Dispatch event
                     */
                    $event = new FilterTagEvent($tag);
                    $this->get('dispatcher')->dispatch(TagEvents::TAG_CREATED, $event);

                    $msg = $this->getTranslator()->trans('child.tag.%name%.created', ['%name%' => $tag->getTagName()]);
                    $this->publishConfirmMessage($request, $msg);

                    return $this->redirect($this->generateUrl(
                        'tagsEditPage',
                        ['tagId' => $tag->getId()]
                    ));
                } catch (EntityAlreadyExistsException $e) {
                    $form->addError(new FormError($e->getMessage()));
                }
            }

            $this->assignation['translation'] = $translation;
            $this->assignation['form'] = $form->createView();
            $this->assignation['parentTag'] = $parentTag;

            return $this->render('tags/add.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Handle tag nodes page.
     *
     * @param Request $request
     * @param int     $tagId
     *
     * @return Response
     * @throws Twig_Error_Runtime
     */
    public function editNodesAction(Request $request, $tagId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_TAGS');

        $tag = $this->get('em')
            ->find(Tag::class, (int) $tagId);

        if (null !== $tag) {
            $translation = $this->get('defaultTranslation');

            $this->assignation['tag'] = $tag;

            /*
             * Manage get request to filter list
             */
            $listManager = $this->createEntityListManager(
                Node::class,
                [
                    'tags' => $tag,
                ]
            );
            $listManager->setDisplayingNotPublishedNodes(true);
            $listManager->handle();

            $this->assignation['filters'] = $listManager->getAssignation();
            $this->assignation['nodes'] = $listManager->getEntities();
            $this->assignation['translation'] = $translation;

            return $this->render('tags/nodes.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * @param Tag $tag
     *
     * @return FormInterface
     */
    private function buildDeleteForm(Tag $tag)
    {
        $builder = $this->createFormBuilder()
            ->add('tagId', HiddenType::class, [
                'data' => $tag->getId(),
                'constraints' => [
                    new NotBlank(),
                ],
            ]);

        return $builder->getForm();
    }

    /**
     * @param bool  $referer
     * @param array $tagsIds
     *
     * @return Form
     */
    private function buildBulkDeleteForm(
        $referer = false,
        $tagsIds = []
    ) {
        /** @var FormBuilder $builder */
        $builder = $this->get('formFactory')
            ->createNamedBuilder('deleteForm')
            ->add('tagsIds', HiddenType::class, [
                'data' => implode(',', $tagsIds),
                'attr' => ['class' => 'tags-id-bulk-tags'],
                'constraints' => [
                    new NotBlank(),
                ],
            ]);

        if (false !== $referer) {
            $builder->add('referer', HiddenType::class, [
                'data' => $referer,
            ]);
        }

        return $builder->getForm();
    }

    /**
     * @param array $data
     *
     * @return string
     */
    private function bulkDeleteTags($data)
    {
        if (!empty($data['tagsIds'])) {
            $tagsIds = trim($data['tagsIds']);
            $tagsIds = explode(',', $tagsIds);
            array_filter($tagsIds);

            $tags = $this->get('em')
                ->getRepository(Tag::class)
                ->findBy([
                    'id' => $tagsIds,
                ]);

            /** @var Tag $tag */
            foreach ($tags as $tag) {
                /** @var TagHandler $handler */
                $handler = $this->get('factory.handler')->getHandler($tag);
                $handler->removeWithChildrenAndAssociations();
            }

            $this->get('em')->flush();

            return $this->getTranslator()->trans('tags.bulk.deleted');
        }

        return $this->getTranslator()->trans('wrong.request');
    }

    protected function onPostUpdate(AbstractEntity $entity, Request $request): void
    {
        if ($entity instanceof TagTranslation) {
            $this->get('em')->flush();
            /*
             * Dispatch event
             */
            $this->get('dispatcher')->dispatch(
                TagEvents::TAG_UPDATED,
                new FilterTagEvent($entity->getTag())
            );

            $msg = $this->getTranslator()->trans('tag.%name%.updated', [
                '%name%' => $entity->getName(),
            ]);
            $this->publishConfirmMessage($request, $msg);
        }
    }

    protected function getPostUpdateRedirection(AbstractEntity $entity): ?Response
    {
        if ($entity instanceof TagTranslation) {
            return $this->redirect($this->generateUrl(
                'tagsEditTranslatedPage',
                ['tagId' => $entity->getTag()->getId(), 'translationId' => $entity->getTranslation()->getId()]
            ));
        }
        return null;
    }
}
