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
use RZ\Roadiz\Core\ListManagers\EntityListManager;
use Themes\Rozier\RozierApp;

use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
* Translation's controller
*/
class TranslationsController extends RozierApp
{
    const ITEM_PER_PAGE = 5;

    /**
     * List every translations.
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_TRANSLATIONS');

        $translations = $this->getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Translation')
            ->findAll();

        $this->assignation['translations'] = [];

        $listManager = new EntityListManager(
            $request,
            $this->getService('em'),
            'RZ\Roadiz\Core\Entities\Translation'
        );
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();

        foreach ($translations as $translation) {
            // Make default forms
            $form = $this->buildMakeDefaultForm($translation);
            $form->handleRequest();
            if ($form->isValid() &&
                $form->getData()['translationId'] == $translation->getId()) {
                $translation->getHandler()->makeDefault();

                $msg = $this->getTranslator()->trans('translation.%name%.made_default', ['%name%'=>$translation->getName()]);
                $this->publishConfirmMessage($request, $msg);
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate(
                        'translationsHomePage'
                    )
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['translations'][] = [
                'translation' => $translation,
                'defaultForm' => $form->createView()
            ];
        }

        return new Response(
            $this->getTwig()->render('translations/list.html.twig', $this->assignation),
            Response::HTTP_OK,
            ['content-type' => 'text/html']
        );
    }

    /**
     * Return an edition form for requested translation.
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param integer                                  $translationId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $translationId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_TRANSLATIONS');

        $translation = $this->getService('em')
            ->find('RZ\Roadiz\Core\Entities\Translation', (int) $translationId);

        if ($translation !== null) {
            $this->assignation['translation'] = $translation;

            $form = $this->buildEditForm($translation);
            $form->handleRequest();

            if ($form->isValid()) {
                try {
                    $this->editTranslation($form->getData(), $translation);

                    $msg = $this->getTranslator()->trans('translation.%name%.updated', ['%name%'=>$translation->getName()]);
                    $this->publishConfirmMessage($request, $msg);
                } catch (EntityAlreadyExistsException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                }

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate(
                        'translationsEditPage',
                        ['translationId' => $translation->getId()]
                    )
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('translations/edit.html.twig', $this->assignation),
                Response::HTTP_OK,
                ['content-type' => 'text/html']
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return an creation form for requested translation.
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_TRANSLATIONS');

        $translation = new Translation();

        if (null !== $translation) {
            $this->assignation['translation'] = $translation;

            $form = $this->buildEditForm($translation);

            $form->handleRequest();

            if ($form->isValid()) {
                try {
                    $this->addTranslation($form->getData(), $translation);

                    $msg = $this->getTranslator()->trans('translation.%name%.created', ['%name%'=>$translation->getName()]);
                    $this->publishConfirmMessage($request, $msg);
                } catch (EntityAlreadyExistsException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                }
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate('translationsHomePage')
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('translations/add.html.twig', $this->assignation),
                Response::HTTP_OK,
                ['content-type' => 'text/html']
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return an deletion form for requested translation.
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $translationId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $translationId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_TRANSLATIONS');

        $translation = $this->getService('em')
            ->find('RZ\Roadiz\Core\Entities\Translation', (int) $translationId);

        if (null !== $translation) {
            $this->assignation['translation'] = $translation;

            $form = $this->buildDeleteForm($translation);

            $form->handleRequest();

            if ($form->isValid() &&
                $form->getData()['translationId'] == $translation->getId()) {
                try {
                    $this->deleteTranslation($form->getData(), $translation);

                    $msg = $this->getTranslator()->trans('translation.%name%.deleted', ['%name%'=>$translation->getName()]);
                    $this->publishConfirmMessage($request, $msg);
                } catch (\Exception $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                }
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate('translationsHomePage')
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('translations/delete.html.twig', $this->assignation),
                Response::HTTP_OK,
                ['content-type' => 'text/html']
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * @param array                              $data
     * @param RZ\Roadiz\Core\Entities\Translation $translation
     *
     * @return void
     */
    private function editTranslation($data, Translation $translation)
    {
        try {
            foreach ($data as $key => $value) {
                $setter = 'set'.ucwords($key);
                $translation->$setter( $value );
            }

            $this->getService('em')->flush();
        } catch (\Exception $e) {
            throw new EntityAlreadyExistsException(
                $this->getTranslator()->trans(
                    'translation.%locale%.cannot_update_already_exists',
                    ['%locale%'=>$translation->getLocale()]
                ),
                1
            );
        }
    }

    /**
     * @param array                              $data
     * @param RZ\Roadiz\Core\Entities\Translation $translation
     *
     * @return void
     */
    private function addTranslation($data, Translation $translation)
    {
        try {
            foreach ($data as $key => $value) {
                $setter = 'set'.ucwords($key);
                $translation->$setter( $value );
            }
            $this->getService('em')->persist($translation);
            $this->getService('em')->flush();
        } catch (\Exception $e) {
            throw new EntityAlreadyExistsException(
                $this->getTranslator()->trans(
                    'translation.%locale%.cannot_create_already_exists',
                    ['%locale%'=>$translation->getLocale()]
                ),
                1
            );
        }
    }

    /**
     * @param array                              $data
     * @param RZ\Roadiz\Core\Entities\Translation $translation
     *
     * @return void
     */
    private function deleteTranslation($data, Translation $translation)
    {
        if ($data['translationId'] == $translation->getId()) {
            if (false === $translation->isDefaultTranslation()) {
                $this->getService('em')->remove($translation);
                $this->getService('em')->flush();
            } else {
                throw new \Exception(
                    $this->getTranslator()->trans(
                        'translation.%name%.cannot_delete_default_translation',
                        ['%name%'=>$translation->getName()]
                    ),
                    1
                );
            }
        }
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Translation $translation
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildEditForm(Translation $translation)
    {
        $defaults = [
            'name' =>           $translation->getName(),
            'locale' =>         $translation->getLocale(),
            'available' =>      $translation->isAvailable(),
        ];
        $builder = $this->getService('formFactory')
            ->createBuilder('form', $defaults)
            ->add(
                'name',
                'text',
                [
                    'label'=>$this->getTranslator()->trans('name'),
                    'constraints' => [
                        new NotBlank()
                    ]
                ]
            )
            ->add(
                'locale',
                'choice',
                [
                    'label'=>$this->getTranslator()->trans('locale'),
                    'required' => true,
                    'choices' => Translation::$availableLocales
                ]
            )
            ->add(
                'available',
                'checkbox',
                [
                    'label'=>$this->getTranslator()->trans('available'),
                    'required' => false
                ]
            );

        return $builder->getForm();
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Translation $translation
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildDeleteForm(Translation $translation)
    {
        $builder = $this->getService('formFactory')
            ->createBuilder('form')
            ->add(
                'translationId',
                'hidden',
                [
                    'data' => $translation->getId(),
                    'constraints' => [
                        new NotBlank()
                    ]
                ]
            );

        return $builder->getForm();
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Translation $translation
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildMakeDefaultForm(Translation $translation)
    {
        $builder = $this->getService('formFactory')
            ->createBuilder('form')
            ->add(
                'translationId',
                'hidden',
                [
                    'data' => $translation->getId(),
                    'constraints' => [
                        new NotBlank()
                    ]
                ]
            );

        return $builder->getForm();
    }
}
