<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file ThemesController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Theme;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\CMS\Controllers\FrontendController;
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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Finder\Finder;

/**
 * {@inheritdoc}
 */
class ThemesController extends RozierApp
{
    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_THEMES');

        $listManager = new EntityListManager(
            $request,
            $this->getService('em'),
            'RZ\Renzo\Core\Entities\Theme'
        );
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['themes'] = $listManager->getEntities();

        return new Response(
            $this->getTwig()->render('themes/list.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * Return a creation form for requested theme.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_THEMES');

        $theme = new Theme();

        $form = $this->buildAddForm($theme);

        $form->handleRequest();

        if ($form->isValid()) {
            try {
                $this->addTheme($form->getData(), $theme);
                $msg = $this->getTranslator()->trans('theme.%name%.created', array('%name%'=>$theme->getClassName()));
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getService('logger')->info($msg);

            } catch (EntityAlreadyExistsException $e) {
                $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                $this->getService('logger')->warning($e->getMessage());
            }

            $response = new RedirectResponse(
                $this->getService('urlGenerator')->generate('themesHomePage')
            );
            $response->prepare($request);

            return $response->send();
        }

        $this->assignation['form'] = $form->createView();

        return new Response(
            $this->getTwig()->render('themes/add.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );

    }

    /**
     * Returns an edition form for the requested theme.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param integer                                  $themeId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $themeId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_THEMES');

        $theme = $this->getService('em')
            ->find('RZ\Renzo\Core\Entities\Theme', (int) $themeId);

        if ($theme !== null) {

            $form = $this->buildEditForm($theme);
            $form->handleRequest();

            if ($form->isValid()) {

                try {
                    $this->editTheme($form->getData(), $theme);
                    $msg = $this->getTranslator()->trans('theme.%name%.updated', array('%name%'=>$theme->getClassName()));
                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getService('logger')->info($msg);
                } catch (EntityAlreadyExistsException $e) {
                    $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                    $this->getService('logger')->warning($e->getMessage());
                } catch (\RuntimeException $e) {
                    $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                    $this->getService('logger')->warning($e->getMessage());
                }

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate('themesHomePage')
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();
            $this->assignation['theme'] = $theme;

            return new Response(
                $this->getTwig()->render('themes/edit.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return a deletion form for requested theme.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param integer                                  $themeId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $themeId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_THEMES');

        $theme = $this->getService('em')
            ->find('RZ\Renzo\Core\Entities\Theme', (int) $themeId);

        if ($theme !== null) {
            $form = $this->buildDeleteForm($theme);
            $form->handleRequest();

            if ($form->isValid() &&
                $form->getData()['themeId'] == $theme->getId()) {

                try {
                    $this->deleteTheme($form->getData(), $theme);
                    $msg = $this->getTranslator()->trans('theme.%name%.deleted', array('%name%'=>$theme->getClassName()));
                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getService('logger')->info($msg);

                } catch (EntityRequiredException $e) {
                    $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                    $this->getService('logger')->warning($e->getMessage());
                } catch (\RuntimeException $e) {
                    $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                    $this->getService('logger')->warning($e->getMessage());
                }

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate('themesHomePage')
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();
            $this->assignation['theme'] = $theme;

            return new Response(
                $this->getTwig()->render('themes/delete.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * Build add theme form with classname constraint.
     *
     * @param RZ\Renzo\Core\Entities\Theme $theme
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function buildAddForm(Theme $theme)
    {
        $builder = $this->getService('formFactory')
            ->createBuilder('form')
            ->add(
                'className',
                new \RZ\Renzo\CMS\Forms\ThemesType(),
                array(
                    'label' => $this->getTranslator()->trans('themeClass'),
                    'required' => true,
                    'constraints' => array(
                        new \Symfony\Component\Validator\Constraints\NotNull(),
                        new \Symfony\Component\Validator\Constraints\Type('string'),
                    )
                )
            )
            ->add(
                'available',
                'checkbox',
                array(
                    'label' => $this->getTranslator()->trans('available'),
                    'data' => $theme->isAvailable(),
                    'required' => false
                )
            )
            ->add(
                'hostname',
                'text',
                array(
                    'label' => $this->getTranslator()->trans('hostname'),
                    'data' => $theme->getHostname()
                )
            )
            ->add(
                'backendTheme',
                'checkbox',
                array(
                    'label' => $this->getTranslator()->trans('backendTheme'),
                    'data' => $theme->isBackendTheme(),
                    'required' => false
                )
            );

        return $builder->getForm();
    }

    /**
     * Build edit theme form with classname constraint.
     *
     * @param RZ\Renzo\Core\Entities\Theme $theme
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function buildEditForm(Theme $theme)
    {
        $defaults = array(
            'available' =>    $theme->isAvailable(),
            'className' =>    $theme->getClassName(),
            'hostname' =>     $theme->getHostname(),
            'backendTheme' => $theme->isBackendTheme()
        );

        $builder = $this->getService('formFactory')
            ->createBuilder('form', $defaults)
            ->add('available', 'checkbox', array(
                'label' => $this->getTranslator()->trans('available'),
                'data' => $theme->isAvailable(),
                'required' => false
            ))
            ->add('hostname', 'text', array(
                'label' => $this->getTranslator()->trans('hostname'),
                'data' => $theme->getHostname()
            ))
            ->add('backendTheme', 'checkbox', array(
                'label' => $this->getTranslator()->trans('backendTheme'),
                'data' => $theme->isBackendTheme(),
                'required' => false
            ));

        return $builder->getForm();
    }

    /**
     * Build delete theme form with classname constraint.
     *
     * @param RZ\Renzo\Core\Entities\Theme $theme
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function buildDeleteForm(Theme $theme)
    {
        $builder = $this->getService('formFactory')
            ->createBuilder('form')
            ->add('themeId', 'hidden', array(
                'data'=>$theme->getId()
            ));

        return $builder->getForm();
    }

    /**
     * @param array                        $data
     * @param RZ\Renzo\Core\Entities\Theme $theme
     */
    private function addTheme(array $data, Theme $theme)
    {
        foreach ($data as $key => $value) {
            $setter = 'set'.ucwords($key);
            $theme->$setter($value);
        }

        $existing = $this->getService('em')
            ->getRepository('RZ\Renzo\Core\Entities\Theme')
            ->findOneBy(array('className'=>$theme->getClassName()));

        if ($existing !== null) {
            throw new EntityAlreadyExistsException(
                $this->getTranslator()->trans(
                    'theme.%name%.no_creation.already_exists',
                    array('%name%'=>$theme->getClassName())
                ),
                1
            );
        }

        $this->getService('em')->persist($theme);
        $this->getService('em')->flush();
    }

    /**
     * @param array                        $data
     * @param RZ\Renzo\Core\Entities\Theme $theme
     *
     * @return boolean
     */
    private function editTheme(array $data, Theme $theme)
    {
        foreach ($data as $key => $value) {
            $setter = 'set'.ucwords($key);
            $theme->$setter($value);
        }

        $this->getService('em')->flush();

        return true;
    }

    /**
     * @param array                        $data
     * @param RZ\Renzo\Core\Entities\Theme $theme
     */
    protected function deleteTheme(array $data, Theme $theme)
    {
        $this->getService('em')->remove($theme);
        $this->getService('em')->flush();
    }
}
