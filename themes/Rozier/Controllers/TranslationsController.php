<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers;

use Exception;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Events\Translation\TranslationCreatedEvent;
use RZ\Roadiz\Core\Events\Translation\TranslationDeletedEvent;
use RZ\Roadiz\Core\Events\Translation\TranslationUpdatedEvent;
use RZ\Roadiz\Core\Handlers\TranslationHandler;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Themes\Rozier\Forms\TranslationType;
use Themes\Rozier\RozierApp;

class TranslationsController extends RozierApp
{
    const ITEM_PER_PAGE = 5;

    /**
     * List every translations.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_TRANSLATIONS');

        $this->assignation['translations'] = [];

        $listManager = $this->createEntityListManager(
            Translation::class
        );
        $listManager->setDisplayingNotPublishedNodes(true);
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $translations = $listManager->getEntities();

        /** @var Translation $translation */
        foreach ($translations as $translation) {
            // Make default forms
            $form = $this->buildMakeDefaultForm($translation);
            $form->handleRequest($request);
            if ($form->isSubmitted() &&
                $form->isValid() &&
                $form->getData()['translationId'] == $translation->getId()) {

                /** @var TranslationHandler $handler */
                $handler = $this->get('translation.handler');
                $handler->setTranslation($translation);
                $handler->makeDefault();

                $msg = $this->getTranslator()->trans('translation.%name%.made_default', ['%name%' => $translation->getName()]);
                $this->publishConfirmMessage($request, $msg);

                $this->get('dispatcher')->dispatch(new TranslationUpdatedEvent($translation));
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl(
                    'translationsHomePage'
                ));
            }

            $this->assignation['translations'][] = [
                'translation' => $translation,
                'defaultForm' => $form->createView(),
            ];
        }

        return $this->render('translations/list.html.twig', $this->assignation);
    }

    /**
     * Return an edition form for requested translation.
     *
     * @param Request $request
     * @param int $translationId
     *
     * @return Response
     */
    public function editAction(Request $request, int $translationId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_TRANSLATIONS');

        /** @var Translation|null $translation */
        $translation = $this->get('em')->find(Translation::class, $translationId);

        if ($translation !== null) {
            $this->assignation['translation'] = $translation;

            $form = $this->createForm(TranslationType::class, $translation);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $this->get('em')->flush();

                $msg = $this->getTranslator()->trans('translation.%name%.updated', ['%name%' => $translation->getName()]);
                $this->publishConfirmMessage($request, $msg);

                $this->get('dispatcher')->dispatch(new TranslationUpdatedEvent($translation));
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl(
                    'translationsEditPage',
                    ['translationId' => $translation->getId()]
                ));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('translations/edit.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Return an creation form for requested translation.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function addAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_TRANSLATIONS');

        $translation = new Translation();
        $this->assignation['translation'] = $translation;

        $form = $this->createForm(TranslationType::class, $translation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get('em')->persist($translation);
            $this->get('em')->flush();

            $msg = $this->getTranslator()->trans('translation.%name%.created', ['%name%' => $translation->getName()]);
            $this->publishConfirmMessage($request, $msg);

            $this->get('dispatcher')->dispatch(new TranslationCreatedEvent($translation));
            /*
             * Force redirect to avoid resending form when refreshing page
             */
            return $this->redirect($this->generateUrl('translationsHomePage'));
        }

        $this->assignation['form'] = $form->createView();

        return $this->render('translations/add.html.twig', $this->assignation);
    }

    /**
     * Return an deletion form for requested translation.
     *
     * @param Request $request
     * @param int     $translationId
     *
     * @return Response
     */
    public function deleteAction(Request $request, int $translationId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_TRANSLATIONS');

        /** @var Translation|null $translation */
        $translation = $this->get('em')->find(Translation::class, $translationId);

        if (null !== $translation) {
            $this->assignation['translation'] = $translation;

            $form = $this->buildDeleteForm($translation);

            $form->handleRequest($request);

            if ($form->isSubmitted() &&
                $form->isValid() &&
                $form->getData()['translationId'] == $translation->getId()) {
                try {
                    $this->deleteTranslation($form->getData(), $translation);

                    $msg = $this->getTranslator()->trans('translation.%name%.deleted', ['%name%' => $translation->getName()]);
                    $this->publishConfirmMessage($request, $msg);

                    $this->get('dispatcher')->dispatch(new TranslationDeletedEvent($translation));
                } catch (Exception $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                }
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl('translationsHomePage'));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('translations/delete.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * @param array       $data
     * @param Translation $translation
     *
     * @throws Exception
     */
    private function deleteTranslation($data, Translation $translation)
    {
        if ($data['translationId'] == $translation->getId()) {
            if (false === $translation->isDefaultTranslation()) {
                $this->get('em')->remove($translation);
                $this->get('em')->flush();
            } else {
                throw new Exception(
                    $this->getTranslator()->trans(
                        'translation.%name%.cannot_delete_default_translation',
                        ['%name%' => $translation->getName()]
                    ),
                    1
                );
            }
        }
    }

    /**
     * @param Translation $translation
     *
     * @return FormInterface
     */
    private function buildDeleteForm(Translation $translation)
    {
        $builder = $this->createFormBuilder()
                        ->add(
                            'translationId',
                            HiddenType::class,
                            [
                                'data' => $translation->getId(),
                                'constraints' => [
                                    new NotNull(),
                                    new NotBlank(),
                                ],
                            ]
                        );

        return $builder->getForm();
    }

    /**
     * @param Translation $translation
     *
     * @return FormInterface
     */
    private function buildMakeDefaultForm(Translation $translation)
    {
        $builder = $this->createFormBuilder()
                        ->add(
                            'translationId',
                            HiddenType::class,
                            [
                                'data' => $translation->getId(),
                                'constraints' => [
                                    new NotNull(),
                                    new NotBlank(),
                                ],
                            ]
                        );

        return $builder->getForm();
    }
}
