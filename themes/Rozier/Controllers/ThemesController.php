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
 * @file ThemesController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\Theme;
use RZ\Roadiz\Core\ListManagers\EntityListManager;
use Themes\Rozier\RozierApp;

use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use RZ\Roadiz\Core\Exceptions\EntityRequiredException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Validator\Constraints\Type;

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
            'RZ\Roadiz\Core\Entities\Theme'
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
                $msg = $this->getTranslator()->trans(
                    'theme.%name%.created',
                    array('%name%'=>$theme->getClassName())
                );
                $this->publishConfirmMessage($request, $msg);

            } catch (EntityAlreadyExistsException $e) {
                $this->publishErrorMessage($request, $e->getMessage());
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
            ->find('RZ\Roadiz\Core\Entities\Theme', (int) $themeId);

        if ($theme !== null) {
            $form = $this->buildEditForm($theme);
            $form->handleRequest();

            if ($form->isValid()) {
                try {
                    $this->editTheme($form->getData(), $theme);
                    $msg = $this->getTranslator()->trans(
                        'theme.%name%.updated',
                        array('%name%'=>$theme->getClassName())
                    );
                    $this->publishConfirmMessage($request, $msg);
                } catch (EntityAlreadyExistsException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                } catch (\RuntimeException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                }

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
            ->find('RZ\Roadiz\Core\Entities\Theme', (int) $themeId);

        if ($theme !== null) {
            $form = $this->buildDeleteForm($theme);
            $form->handleRequest();

            if ($form->isValid() &&
                $form->getData()['themeId'] == $theme->getId()) {
                try {
                    $this->deleteTheme($form->getData(), $theme);
                    $msg = $this->getTranslator()->trans(
                        'theme.%name%.deleted',
                        array('%name%'=>$theme->getClassName())
                    );
                    $this->publishConfirmMessage($request, $msg);

                } catch (EntityRequiredException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                } catch (\RuntimeException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                }

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
     * @param RZ\Roadiz\Core\Entities\Theme $theme
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function buildAddForm(Theme $theme)
    {
        $builder = $this->getService('formFactory')
            ->createBuilder('form')
            ->add(
                'className',
                new \RZ\Roadiz\CMS\Forms\ThemesType(),
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
                'staticTheme',
                'checkbox',
                array(
                    'label' => $this->getTranslator()->trans('staticTheme'),
                    'data' => $theme->isStaticTheme(),
                    'required' => false,
                    'attr' => array(
                        'data-desc' => $this->getTranslator()->trans('staticTheme.does_not.allow.node_url_routes')
                    )
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
     * @param RZ\Roadiz\Core\Entities\Theme $theme
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
            ->add(
                'staticTheme',
                'checkbox',
                array(
                    'label' => $this->getTranslator()->trans('staticTheme'),
                    'data' => $theme->isStaticTheme(),
                    'required' => false,
                    'attr' => array(
                        'data-desc' => $this->getTranslator()->trans('staticTheme.does_not.allow.node_url_routes')
                    )
                )
            )
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
     * @param RZ\Roadiz\Core\Entities\Theme $theme
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
     * @param RZ\Roadiz\Core\Entities\Theme $theme
     */
    private function addTheme(array $data, Theme $theme)
    {
        foreach ($data as $key => $value) {
            $setter = 'set'.ucwords($key);
            $theme->$setter($value);
        }

        $existing = $this->getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Theme')
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

        // Clear result cache
        $cacheDriver = Kernel::getService('em')->getConfiguration()->getResultCacheImpl();
        if ($cacheDriver !== null) {
            $cacheDriver->deleteAll();
        }
    }

    /**
     * @param array                        $data
     * @param RZ\Roadiz\Core\Entities\Theme $theme
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

        // Clear result cache
        $cacheDriver = Kernel::getService('em')->getConfiguration()->getResultCacheImpl();
        if ($cacheDriver !== null) {
            $cacheDriver->deleteAll();
        }

        return true;
    }

    /**
     * @param array                        $data
     * @param RZ\Roadiz\Core\Entities\Theme $theme
     */
    protected function deleteTheme(array $data, Theme $theme)
    {
        $this->getService('em')->remove($theme);
        $this->getService('em')->flush();

        // Clear result cache
        $cacheDriver = Kernel::getService('em')->getConfiguration()->getResultCacheImpl();
        if ($cacheDriver !== null) {
            $cacheDriver->deleteAll();
        }
    }
}
