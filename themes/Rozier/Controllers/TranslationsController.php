<?php
/**
 * Copyright REZO ZERO 2014
 *
 *
 *
 *
 * @file TranslationsController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */

namespace Themes\Rozier\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Entities\NodeTypeField;
use RZ\Renzo\Core\ListManagers\EntityListManager;
use Themes\Rozier\RozierApp;

use RZ\Renzo\Core\Exceptions\EntityAlreadyExistsException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use \Symfony\Component\Form\Form;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

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
        $this->validedAccessForRole('ROLE_ACCESS_TRANSLATIONS');

        $translations = $this->getService('em')
            ->getRepository('RZ\Renzo\Core\Entities\Translation')
            ->findAll();

        $this->assignation['translations'] = array();

        $listManager = new EntityListManager(
            $request,
            $this->getService('em'),
            'RZ\Renzo\Core\Entities\Translation'
        );
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();

        foreach ($translations as $translation) {

            // Make default forms
            $form = $this->buildMakeDefaultForm($translation);
            $form->handleRequest();
            if ($form->isValid() &&
                $form->getData()['translation_id'] == $translation->getId()) {

                $translation->getHandler()->makeDefault();

                $msg = $this->getTranslator()->trans('translation.made_default', array('%name%'=>$translation->getName()));
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getLogger()->info($msg);
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

            $this->assignation['translations'][] = array(
                'translation' => $translation,
                'defaultForm' => $form->createView()
            );
        }

        return new Response(
            $this->getTwig()->render('translations/list.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
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
        $this->validedAccessForRole('ROLE_ACCESS_TRANSLATIONS');

        $translation = $this->getService('em')
            ->find('RZ\Renzo\Core\Entities\Translation', (int) $translationId);

        if ($translation !== null) {
            $this->assignation['translation'] = $translation;

            $form = $this->buildEditForm($translation);
            $form->handleRequest();

            if ($form->isValid()) {

                try {
                    $this->editTranslation($form->getData(), $translation);

                    $msg = $this->getTranslator()->trans('translation.updated', array('%name%'=>$translation->getName()));
                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getLogger()->info($msg);
                } catch (EntityAlreadyExistsException $e) {
                    $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                    $this->getLogger()->warning($e->getMessage());
                }

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate(
                        'translationsEditPage',
                        array('translationId' => $translation->getId())
                    )
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('translations/edit.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
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
        $this->validedAccessForRole('ROLE_ACCESS_TRANSLATIONS');

        $translation = new Translation();

        if (null !== $translation) {
            $this->assignation['translation'] = $translation;

            $form = $this->buildEditForm($translation);

            $form->handleRequest();

            if ($form->isValid()) {

                try {
                    $this->addTranslation($form->getData(), $translation);

                    $msg = $this->getTranslator()->trans('translation.created', array('%name%'=>$translation->getName()));
                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getLogger()->info($msg);
                } catch (EntityAlreadyExistsException $e) {
                    $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                    $this->getLogger()->warning($e->getMessage());
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
                array('content-type' => 'text/html')
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
        $this->validedAccessForRole('ROLE_ACCESS_TRANSLATIONS');

        $translation = $this->getService('em')
            ->find('RZ\Renzo\Core\Entities\Translation', (int) $translationId);

        if (null !== $translation) {
            $this->assignation['translation'] = $translation;

            $form = $this->buildDeleteForm($translation);

            $form->handleRequest();

            if ($form->isValid() &&
                $form->getData()['translationId'] == $translation->getId()) {

                try {
                    $this->deleteTranslation($form->getData(), $translation);

                    $msg = $this->getTranslator()->trans('translation.deleted', array('%name%'=>$translation->getName()));
                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getLogger()->info($msg);
                } catch (\Exception $e) {
                    $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                    $this->getLogger()->warning($e->getMessage());
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
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * @param array                              $data
     * @param RZ\Renzo\Core\Entities\Translation $translation
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
                    'translation.cannot_update_already_exists',
                    array('%locale%'=>$translation->getLocale())
                ),
                1
            );
        }
    }

    /**
     * @param array                              $data
     * @param RZ\Renzo\Core\Entities\Translation $translation
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
                    'translation.cannot_create_already_exists',
                    array('%locale%'=>$translation->getLocale())
                ),
                1
            );
        }
    }

    /**
     * @param array                              $data
     * @param RZ\Renzo\Core\Entities\Translation $translation
     *
     * @return void
     */
    private function deleteTranslation($data, Translation $translation)
    {
        if ($data['translation_id'] == $translation->getId()) {

            if (false === $translation->isDefaultTranslation()) {
                $this->getService('em')->remove($translation);
                $this->getService('em')->flush();
            } else {
                throw new \Exception(
                    $this->getTranslator()->trans(
                        'translation.cannot_delete_default_translation',
                        array('%name%'=>$translation->getName())
                    ),
                    1
                );
            }
        }
    }

    /**
     * @param RZ\Renzo\Core\Entities\Translation $translation
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildEditForm(Translation $translation)
    {
        $defaults = array(
            'name' =>           $translation->getName(),
            'locale' =>         $translation->getLocale(),
            'available' =>      $translation->isAvailable(),
        );
        $builder = $this->getFormFactory()
            ->createBuilder('form', $defaults)
            ->add(
                'name',
                'text',
                array(
                    'constraints' => array(
                        new NotBlank()
                    )
                )
            )
            ->add(
                'locale',
                'choice',
                array(
                    'required' => true,
                    'choices' => Translation::$availableLocales
                )
            )
            ->add(
                'available',
                'checkbox',
                array('required' => false)
            );

        return $builder->getForm();
    }

    /**
     * @param RZ\Renzo\Core\Entities\Translation $translation
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildDeleteForm(Translation $translation)
    {
        $builder = $this->getFormFactory()
            ->createBuilder('form')
            ->add(
                'translation_id',
                'hidden',
                array(
                    'data' => $translation->getId(),
                    'constraints' => array(
                        new NotBlank()
                    )
                )
            );

        return $builder->getForm();
    }

    /**
     * @param RZ\Renzo\Core\Entities\Translation $translation
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildMakeDefaultForm(Translation $translation)
    {
        $builder = $this->getFormFactory()
            ->createBuilder('form')
            ->add(
                'translation_id',
                'hidden',
                array(
                    'data' => $translation->getId(),
                    'constraints' => array(
                        new NotBlank()
                    )
                )
            );

        return $builder->getForm();
    }
}
