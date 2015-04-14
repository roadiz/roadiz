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

use RZ\Roadiz\Core\Entities\Theme;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use RZ\Roadiz\Core\Exceptions\EntityRequiredException;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\ListManagers\EntityListManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Type;
use Themes\Rozier\RozierApp;

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

        $themeType = new \RZ\Roadiz\CMS\Forms\ThemesType();
        $this->assignation['availableThemesCount'] = $themeType->getSize();

        return $this->render('themes/list.html.twig', $this->assignation);
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
                $data = $form->getData();
                $this->addTheme($request, $data, $theme);
                $msg = $this->getTranslator()->trans(
                    'theme.%name%.created',
                    ['%name%' => $theme->getClassName()]
                );
                $this->publishConfirmMessage($request, $msg);

            } catch (EntityAlreadyExistsException $e) {
                $this->publishErrorMessage($request, $e->getMessage());
            }

            return $this->redirect($this->generateUrl('themesHomePage'));
        }

        $this->assignation['form'] = $form->createView();

        return $this->render('themes/add.html.twig', $this->assignation);

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
                    $this->editTheme($request, $form->getData(), $theme);
                    $msg = $this->getTranslator()->trans(
                        'theme.%name%.updated',
                        ['%name%' => $theme->getClassName()]
                    );
                    $this->publishConfirmMessage($request, $msg);
                } catch (EntityAlreadyExistsException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                } catch (\RuntimeException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                }

                return $this->redirect($this->generateUrl('themesHomePage'));
            }

            $this->assignation['form'] = $form->createView();
            $this->assignation['theme'] = $theme;

            return $this->render('themes/edit.html.twig', $this->assignation);
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
                        ['%name%' => $theme->getClassName()]
                    );
                    $this->publishConfirmMessage($request, $msg);

                } catch (EntityRequiredException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                } catch (\RuntimeException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                }

                return $this->redirect($this->generateUrl('themesHomePage'));
            }

            $this->assignation['form'] = $form->createView();
            $this->assignation['theme'] = $theme;

            return $this->render('themes/delete.html.twig', $this->assignation);
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
        $builder = $this->buildCommonForm($theme);

        /*
         * See if its possible to prepend field instead of adding it
         */
        $builder->add(
            'className',
            new \RZ\Roadiz\CMS\Forms\ThemesType(),
            [
                'label' => $this->getTranslator()->trans('themeClass'),
                'required' => true,
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\NotNull(),
                    new \Symfony\Component\Validator\Constraints\Type('string'),
                ],
            ]
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
        return $this->buildCommonForm($theme)->getForm();
    }

    /**
     * @param Theme $theme
     *
     * @return FormBuilder
     */
    protected function buildCommonForm(Theme $theme)
    {
        $n = $theme->getHomeNode();
        $r = $theme->getRoot();

        $defaults = [
            'available' => $theme->isAvailable(),
            'className' => $theme->getClassName(),
            'staticTheme' => $theme->isStaticTheme(),
            'hostname' => $theme->getHostname(),
            'routePrefix' => $theme->getRoutePrefix(),
            'backendTheme' => $theme->isBackendTheme(),
            'homeNode' => ($n !== null) ? $n->getId() : null,
            'root' => ($r !== null) ? $r->getId() : null,
        ];

        $builder = $this->getService('formFactory')
                        ->createNamedBuilder('source', 'form', $defaults)
                        ->add('available', 'checkbox', [
                            'label' => $this->getTranslator()->trans('available'),
                            'required' => false,
                        ])
                        ->add(
                            'staticTheme',
                            'checkbox',
                            [
                                'label' => $this->getTranslator()->trans('staticTheme'),
                                'required' => false,
                                'attr' => [
                                    'data-desc' => $this->getTranslator()->trans('staticTheme.does_not.allow.node_url_routes'),
                                ],
                            ]
                        )
                        ->add('hostname', 'text', [
                            'label' => $this->getTranslator()->trans('hostname'),
                        ])
                        ->add('routePrefix', 'text', [
                            'label' => $this->getTranslator()->trans('routePrefix'),
                            'required' => false,
                        ])
                        ->add('backendTheme', 'checkbox', [
                            'label' => $this->getTranslator()->trans('backendTheme'),
                            'required' => false,
                        ]);

        $d = ($n !== null) ? [$n] : [];

        $builder->add('homeNode', new \RZ\Roadiz\CMS\Forms\NodesType($d), [
            'label' => $this->getTranslator()->trans('homeNode'),
            'required' => false,
        ]);

        $d = ($r !== null) ? [$r] : [];

        $builder->add('root', new \RZ\Roadiz\CMS\Forms\NodesType($d), [
            'label' => $this->getTranslator()->trans('themeRoot'),
            'required' => false,
        ]);

        return $builder;
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
                        ->add('themeId', 'hidden', [
                            'data' => $theme->getId(),
                        ]);

        return $builder->getForm();
    }

    private function setThemeValue(Request $request, array &$data, Theme $theme)
    {
        foreach ($data as $key => $value) {
            $setter = 'set' . ucwords($key);
            if ($key == "homeNode" || $key == "root") {
                if (count($value) > 1) {
                    if ($key == "root") {
                        $msg = $this->getTranslator()->trans('theme.root.limited.one');
                    } elseif ($key == "homeNode") {
                        $msg = $this->getTranslator()->trans('home.node.limited.one');
                    }
                    $this->publishErrorMessage($request, $msg);
                }
                if ($value !== null && !empty($value[0])) {
                    $n = $this->getService('em')->find("RZ\Roadiz\Core\Entities\Node", $value[0]);
                    $theme->$setter($n);
                } else {
                    $theme->$setter(null);
                }
            } else {
                $theme->$setter($value);
            }
        }
    }

    /**
     * @param Symfony\Component\HttpFoundation\Request  $request
     * @param array                                     $data
     * @param RZ\Roadiz\Core\Entities\Theme             $theme
     */
    private function addTheme(Request $request, array &$data, Theme $theme)
    {
        $this->setThemeValue($request, $data, $theme);

        $existing = $this->getService('em')
                         ->getRepository('RZ\Roadiz\Core\Entities\Theme')
                         ->findOneBy(['className' => $theme->getClassName()]);

        if ($existing !== null) {
            throw new EntityAlreadyExistsException(
                $this->getTranslator()->trans(
                    'theme.%name%.no_creation.already_exists',
                    ['%name%' => $theme->getClassName()]
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
     * @param Symfony\Component\HttpFoundation\Request  $request
     * @param array                                     $data
     * @param RZ\Roadiz\Core\Entities\Theme             $theme
     *
     * @return boolean
     */
    private function editTheme(Request $request, array $data, Theme $theme)
    {
        $this->setThemeValue($request, $data, $theme);

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
