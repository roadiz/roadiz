<?php
/**
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
 *
 *
 * @file TranslationsController.php
 * @author Ambroise Maupate
 */

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Events\FilterTranslationEvent;
use RZ\Roadiz\Core\Events\TranslationEvents;
use RZ\Roadiz\Core\Handlers\TranslationHandler;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Validator\Constraints\NotBlank;
use Themes\Rozier\Forms\TranslationType;
use Themes\Rozier\RozierApp;

/**
 * Translation's controller
 */
class TranslationsController extends RozierApp
{
    const ITEM_PER_PAGE = 5;

    /**
     * List every translations.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_TRANSLATIONS');

        $translations = $this->get('em')
                             ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                             ->findAll();

        $this->assignation['translations'] = [];

        $listManager = $this->createEntityListManager(
            'RZ\Roadiz\Core\Entities\Translation'
        );
        $listManager->setDisplayingNotPublishedNodes(true);
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();

        /** @var Translation $translation */
        foreach ($translations as $translation) {
            // Make default forms
            $form = $this->buildMakeDefaultForm($translation);
            $form->handleRequest($request);
            if ($form->isValid() &&
                $form->getData()['translationId'] == $translation->getId()) {

                /** @var TranslationHandler $handler */
                $handler = $this->get('translation.handler');
                $handler->setTranslation($translation);
                $handler->makeDefault();

                $msg = $this->getTranslator()->trans('translation.%name%.made_default', ['%name%' => $translation->getName()]);
                $this->publishConfirmMessage($request, $msg);

                /*
                 * Dispatch event
                 */
                $event = new FilterTranslationEvent($translation);
                $this->get('dispatcher')->dispatch(TranslationEvents::TRANSLATION_UPDATED, $event);
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
     * @param integer $translationId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $translationId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_TRANSLATIONS');

        $translation = $this->get('em')
                            ->find('RZ\Roadiz\Core\Entities\Translation', (int) $translationId);

        if ($translation !== null) {
            $this->assignation['translation'] = $translation;

            $form = $this->createForm(TranslationType::class, $translation, [
                'em' => $this->get('em'),
                'locale' => $translation->getLocale(),
                'overrideLocale' => $translation->getOverrideLocale(),
            ]);
            $form->handleRequest($request);

            if ($form->isValid()) {
                $this->get('em')->flush();

                $msg = $this->getTranslator()->trans('translation.%name%.updated', ['%name%' => $translation->getName()]);
                $this->publishConfirmMessage($request, $msg);

                /*
                 * Dispatch event
                 */
                $event = new FilterTranslationEvent($translation);
                $this->get('dispatcher')->dispatch(TranslationEvents::TRANSLATION_UPDATED, $event);
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
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_TRANSLATIONS');

        $translation = new Translation();
        $this->assignation['translation'] = $translation;

        $form = $this->createForm(TranslationType::class, $translation, [
            'em' => $this->get('em'),
        ]);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->get('em')->persist($translation);
            $this->get('em')->flush();

            $msg = $this->getTranslator()->trans('translation.%name%.created', ['%name%' => $translation->getName()]);
            $this->publishConfirmMessage($request, $msg);
            /*
             * Dispatch event
             */
            $event = new FilterTranslationEvent($translation);
            $this->get('dispatcher')->dispatch(TranslationEvents::TRANSLATION_CREATED, $event);
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
     * @param int                                      $translationId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $translationId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_TRANSLATIONS');

        $translation = $this->get('em')
                            ->find('RZ\Roadiz\Core\Entities\Translation', (int) $translationId);

        if (null !== $translation) {
            $this->assignation['translation'] = $translation;

            $form = $this->buildDeleteForm($translation);

            $form->handleRequest($request);

            if ($form->isValid() &&
                $form->getData()['translationId'] == $translation->getId()) {
                try {
                    $this->deleteTranslation($form->getData(), $translation);

                    $msg = $this->getTranslator()->trans('translation.%name%.deleted', ['%name%' => $translation->getName()]);
                    $this->publishConfirmMessage($request, $msg);
                    /*
                     * Dispatch event
                     */
                    $event = new FilterTranslationEvent($translation);
                    $this->get('dispatcher')->dispatch(TranslationEvents::TRANSLATION_DELETED, $event);
                } catch (\Exception $e) {
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
     * @throws \Exception
     */
    private function deleteTranslation($data, Translation $translation)
    {
        if ($data['translationId'] == $translation->getId()) {
            if (false === $translation->isDefaultTranslation()) {
                $this->get('em')->remove($translation);
                $this->get('em')->flush();
            } else {
                throw new \Exception(
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
     * @return \Symfony\Component\Form\Form
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
                                    new NotBlank(),
                                ],
                            ]
                        );

        return $builder->getForm();
    }

    /**
     * @param Translation $translation
     *
     * @return \Symfony\Component\Form\Form
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
                                    new NotBlank(),
                                ],
                            ]
                        );

        return $builder->getForm();
    }
}
