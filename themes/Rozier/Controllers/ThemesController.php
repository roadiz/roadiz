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
 * @file ThemesController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers;

use RZ\Roadiz\CMS\Forms\ThemesType;
use RZ\Roadiz\Core\Entities\Theme;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use RZ\Roadiz\Core\Exceptions\EntityRequiredException;
use RZ\Roadiz\Utils\Clearer\DoctrineCacheClearer;
use RZ\Roadiz\Utils\Clearer\OPCacheClearer;
use RZ\Roadiz\Utils\Doctrine\SchemaUpdater;
use RZ\Roadiz\Utils\Installer\ThemeInstaller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Type;
use Themes\Rozier\RozierApp;

/**
 * {@inheritdoc}
 */
class ThemesController extends RozierApp
{
    /**
     * Import theme screen.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function importAction(Request $request, $id)
    {
        $this->validateAccessForRole('ROLE_ACCESS_THEMES');

        $result = $this->get('em')->find('RZ\Roadiz\Core\Entities\Theme', $id);

        $data = ThemeInstaller::getThemeInformation($result->getClassName());

        $this->assignation = array_merge($this->assignation, $data["importFiles"]);
        $this->assignation["themeId"] = $id;

        return $this->render('themes/import.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_THEMES');

        $listManager = $this->createEntityListManager(
            'RZ\Roadiz\Core\Entities\Theme'
        );
        $listManager->setDisplayingNotPublishedNodes(true);
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['themes'] = $listManager->getEntities();

        $themeType = new ThemesType($this->get('em'));
        $this->assignation['availableThemesCount'] = $themeType->getSize();

        return $this->render('themes/list.html.twig', $this->assignation);
    }

    /**
     * Return a creation form for requested theme.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_THEMES');

        $theme = new Theme();
        $form = $this->buildAddForm($theme);
        $form->handleRequest($request);

        if ($form->isValid()) {
            try {
                $data = $form->getData();
                return $this->redirect($this->generateUrl('themesSummaryPage', [
                    'classname' => $data['className'],
                ]));
            } catch (EntityAlreadyExistsException $e) {
                $this->publishErrorMessage($request, $e->getMessage());
            }

            return $this->redirect($this->generateUrl('themesHomePage'));
        }

        $this->assignation['form'] = $form->createView();

        return $this->render('themes/add.html.twig', $this->assignation);
    }

    /**
     * Return a summary for requested theme.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function summaryAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_THEMES');

        ThemeInstaller::assignSummaryInfo($request->get("classname"), $this->assignation, $request->getLocale());

        return $this->render('themes/summary.html.twig', $this->assignation);
    }

    /**
     * Return a setting form for requested theme.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function settingAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_THEMES');

        $theme = new Theme();

        $form = $this->buildSettingForm($theme, $request->get("classname"));

        $form->handleRequest($request);

        if ($form->isValid()) {
            try {
                $data = $form->getData();
                return $this->addTheme($request, $data, $theme);
            } catch (EntityAlreadyExistsException $e) {
                $this->publishErrorMessage($request, $e->getMessage());
            }

            return $this->redirect($this->generateUrl('themesHomePage'));
        }

        $this->assignation['form'] = $form->createView();

        return $this->render('themes/setting.html.twig', $this->assignation);
    }

    /**
     * Returns an edition form for the requested theme.
     *
     * @param Request $request
     * @param integer $themeId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $themeId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_THEMES');

        $theme = $this->get('em')
            ->find('RZ\Roadiz\Core\Entities\Theme', (int) $themeId);

        if ($theme !== null) {
            $form = $this->buildEditForm($theme);
            $form->handleRequest($request);

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
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Return a deletion form for requested theme.
     *
     * @param Request $request
     * @param integer $themeId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $themeId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_THEMES');

        $theme = $this->get('em')
            ->find('RZ\Roadiz\Core\Entities\Theme', (int) $themeId);

        if ($theme !== null) {
            $form = $this->buildDeleteForm($theme);
            $form->handleRequest($request);

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
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Build add theme form with classname constraint.
     *
     * @param Theme $theme
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function buildAddForm(Theme $theme)
    {
        $builder = $this->get('formFactory')
            ->createNamedBuilder('source', 'form', []);

        /*
         * See if its possible to prepend field instead of adding it
         */
        $builder->add(
            'className',
            new ThemesType($this->get('em')),
            [
                'label' => 'themeClass',
                'required' => true,
                'constraints' => [
                    new NotNull(),
                    new Type('string'),
                ],
            ]
        );

        return $builder->getForm();
    }

    /**
     * Build edit theme form with classname constraint.
     *
     * @param Theme $theme
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function buildEditForm(Theme $theme)
    {
        return $this->buildCommonForm($theme)->getForm();
    }

    /**
     * Build setting theme form with classname constraint.
     *
     * @param Theme  $theme
     * @param string $classname
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function buildSettingForm(Theme $theme, $classname)
    {
        $builder = $this->buildCommonForm($theme)
            ->add('classname', 'hidden', [
                'data' => $classname,
            ]);
        return $builder->getForm();
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

        $builder = $this->get('formFactory')
            ->createNamedBuilder('source', 'form', $defaults)
            ->add('available', 'checkbox', [
                'label' => 'available',
                'required' => false,
            ])
            ->add(
                'staticTheme',
                'checkbox',
                [
                    'label' => 'staticTheme',
                    'required' => false,
                    'attr' => [
                        'data-desc' => 'staticTheme.does_not.allow.node_url_routes',
                    ],
                ]
            )
            ->add('hostname', 'text', [
                'label' => 'hostname',
            ])
            ->add('routePrefix', 'text', [
                'label' => 'routePrefix',
                'required' => false,
            ])
            ->add('backendTheme', 'checkbox', [
                'label' => 'backendTheme',
                'required' => false,
            ]);

        $d = ($n !== null) ? [$n] : [];

        $builder->add('homeNode', new \RZ\Roadiz\CMS\Forms\NodesType($d, $this->get('em')), [
            'label' => 'homeNode',
            'required' => false,
            'attr' => [
                'data-nodetypes' => '',
            ],
        ]);

        $d = ($r !== null) ? [$r] : [];

        $builder->add('root', new \RZ\Roadiz\CMS\Forms\NodesType($d, $this->get('em')), [
            'label' => 'themeRoot',
            'required' => false,
            'attr' => [
                'data-nodetypes' => '',
            ],
        ]);

        return $builder;
    }

    /**
     * Build delete theme form with classname constraint.
     *
     * @param Theme $theme
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function buildDeleteForm(Theme $theme)
    {
        $builder = $this->createFormBuilder()
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
                        $this->publishErrorMessage(
                            $request,
                            $this->getTranslator()->trans('theme.root.limited.one')
                        );
                    } elseif ($key == "homeNode") {
                        $this->publishErrorMessage(
                            $request,
                            $this->getTranslator()->trans('home.node.limited.one')
                        );
                    }
                }
                if ($value !== null && !empty($value[0])) {
                    $n = $this->get('em')->find("RZ\Roadiz\Core\Entities\Node", $value[0]);
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
     * @param Request $request
     * @param array   $data
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws EntityAlreadyExistsException
     */
    private function addTheme(Request $request, array &$data)
    {
        $existing = $this->get('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Theme')
            ->findOneBy(['className' => $data["classname"]]);

        if ($existing !== null) {
            throw new EntityAlreadyExistsException(
                $this->getTranslator()->trans(
                    'theme.%name%.no_creation.already_exists',
                    ['%name%' => $existing->getClassName()]
                ),
                1
            );
        }

        $importFile = ThemeInstaller::install($request, $data["classname"], $this->get("em"));
        $theme = $this->get("em")
            ->getRepository("RZ\Roadiz\Core\Entities\Theme")
            ->findOneByClassName($data["classname"]);
        $this->setThemeValue($request, $data, $theme);

        $this->get('em')->flush();

        // Clear result cache
        $cacheDriver = $this->get('em')->getConfiguration()->getResultCacheImpl();
        if ($cacheDriver !== null) {
            $cacheDriver->deleteAll();
        }
        if ($importFile === false) {
            return $this->redirect($this->generateUrl(
                'themesHomePage'
            ));
        } else {
            return $this->redirect($this->generateUrl(
                'themesImportPage',
                ["id" => $theme->getId()]
            ));
        }
    }

    /**
     * @param Request $request
     * @param array   $data
     * @param Theme   $theme
     *
     * @return boolean
     */
    private function editTheme(Request $request, array $data, Theme $theme)
    {
        $this->setThemeValue($request, $data, $theme);

        $this->get('em')->flush();

        // Clear result cache
        $cacheDriver = $this->get('em')->getConfiguration()->getResultCacheImpl();
        if ($cacheDriver !== null) {
            $cacheDriver->deleteAll();
        }

        return true;
    }

    /**
     * @param array $data
     * @param Theme $theme
     */
    protected function deleteTheme(array $data, Theme $theme)
    {
        $this->get('em')->remove($theme);
        $this->get('em')->flush();

        // Clear result cache
        $cacheDriver = $this->get('em')->getConfiguration()->getResultCacheImpl();
        if ($cacheDriver !== null) {
            $cacheDriver->deleteAll();
        }
    }
}
