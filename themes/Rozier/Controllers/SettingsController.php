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
 * @file SettingsController.php
 * @author Ambroise Maupate
 */

namespace Themes\Rozier\Controllers;

use Doctrine\Common\Cache\CacheProvider;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueEntity;
use RZ\Roadiz\CMS\Forms\SettingType;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Entities\Setting;
use RZ\Roadiz\Core\Entities\SettingGroup;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Themes\Rozier\RozierApp;

/**
 * Settings controller
 */
class SettingsController extends RozierApp
{
    /**
     * List every settings.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_SETTINGS');

        if (null !== $response = $this->commonSettingList($request)) {
            return $response->send();
        }

        return $this->render('settings/list.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     * @param int     $settingGroupId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function byGroupAction(Request $request, $settingGroupId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_SETTINGS');

        $settingGroup = $this->get('em')->find(SettingGroup::class, (int) $settingGroupId);

        if ($settingGroup !== null) {
            $this->assignation['settingGroup'] = $settingGroup;

            if (null !== $response = $this->commonSettingList($request, $settingGroup)) {
                return $response->send();
            }

            return $this->render('settings/list.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * @param Request $request
     * @param SettingGroup|null $settingGroup
     * @return null|RedirectResponse
     */
    protected function commonSettingList(Request $request, SettingGroup $settingGroup = null)
    {
        $criteria = [];
        if (null !== $settingGroup) {
            $criteria = ['settingGroup' => $settingGroup];
        }
        /*
         * Manage get request to filter list
         */
        $listManager = $this->createEntityListManager(
            Setting::class,
            $criteria,
            ['name' => 'ASC']
        );
        $listManager->setDisplayingNotPublishedNodes(true);
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $settings = $listManager->getEntities();
        $this->assignation['settings'] = [];

        /** @var Setting $setting */
        foreach ($settings as $setting) {
            /** @var Form $form */
            $form = $this->get('formFactory')->createNamedBuilder($setting->getName(), SettingType::class, $setting, [
                'entityManager' => $this->get('em'),
                'shortEdit' => true,
                'documentFactory' => $this->get('document.factory'),
                'assetPackages' => $this->get('assetPackages'),
            ])->getForm();
            $form->handleRequest($request);
            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    try {
                        $this->get('em')->flush();
                        $msg = $this->getTranslator()->trans(
                            'setting.%name%.updated',
                            ['%name%' => $setting->getName()]
                        );
                        $this->publishConfirmMessage($request, $msg);
                        if (null !== $settingGroup) {
                            return $this->redirect($this->generateUrl(
                                'settingGroupsSettingsPage',
                                ['settingGroupId' => $settingGroup->getId()]
                            ));
                        } else {
                            return $this->redirect($this->generateUrl(
                                'settingsHomePage'
                            ));
                        }
                    } catch (EntityAlreadyExistsException $e) {
                        $form->addError(new FormError($e->getMessage()));
                    }
                } else {
                    foreach ($this->getErrorsAsArray($form) as $error) {
                        $this->publishErrorMessage($request, $error);
                    }
                }
            }

            $document = null;
            if ($setting->getType() == NodeTypeField::DOCUMENTS_T) {
                $document = $this->get('settingsBag')->getDocument($setting->getName());
            }

            $this->assignation['settings'][] = [
                'setting' => $setting,
                'form' => $form->createView(),
                'document' => $document,
            ];
        }

        return null;
    }

    /**
     * Return an edition form for requested setting.
     *
     * @param Request $request
     * @param int     $settingId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $settingId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_SETTINGS');
        /** @var Setting|null $setting */
        $setting = $this->get('em')->find(Setting::class, (int) $settingId);

        if ($setting !== null) {
            $this->assignation['setting'] = $setting;

            $form = $this->createForm(SettingType::class, $setting, [
                'entityManager' => $this->get('em'),
                'shortEdit' => false,
                'documentFactory' => $this->get('document.factory'),
                'assetPackages' => $this->get('assetPackages'),
                'constraints' => [
                    new UniqueEntity([
                        'fields' => ['name'],
                        'entityManager' => $this->get('em')
                    ]),
                ]
            ]);
            $form->handleRequest($request);

            if ($form->isValid()) {
                try {
                    $this->get('em')->flush();

                    /** @var CacheProvider $cacheDriver */
                    $cacheDriver = $this->get('em')->getConfiguration()->getResultCacheImpl();
                    if ($cacheDriver !== null) {
                        $cacheDriver->deleteAll();
                    }
                    $msg = $this->getTranslator()->trans('setting.%name%.updated', ['%name%' => $setting->getName()]);
                    $this->publishConfirmMessage($request, $msg);
                    /*
                     * Force redirect to avoid resending form when refreshing page
                     */
                    return $this->redirect($this->generateUrl(
                        'settingsEditPage',
                        ['settingId' => $setting->getId()]
                    ));
                } catch (EntityAlreadyExistsException $e) {
                    $form->addError(new FormError($e->getMessage()));
                }
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('settings/edit.html.twig', $this->assignation);
        }

        throw $this->createNotFoundException();
    }

    /**
     * Return an creation form for requested setting.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_SETTINGS');

        $setting = new Setting();
        $setting->setSettingGroup(null);

        $this->assignation['setting'] = $setting;

        $form = $this->createForm(SettingType::class, $setting, [
            'entityManager' => $this->get('em'),
            'shortEdit' => false,
            'documentFactory' => $this->get('document.factory'),
            'assetPackages' => $this->get('assetPackages'),
            'constraints' => [
                new UniqueEntity([
                    'fields' => ['name'],
                    'entityManager' => $this->get('em')
                ]),
            ]
        ]);

        $form->handleRequest($request);

        if ($form->isValid()) {
            try {
                $this->get('em')->persist($setting);
                $this->get('em')->flush();

                /** @var CacheProvider $cacheDriver */
                $cacheDriver = $this->get('em')->getConfiguration()->getResultCacheImpl();
                if ($cacheDriver !== null) {
                    $cacheDriver->deleteAll();
                }
                $msg = $this->getTranslator()->trans('setting.%name%.created', ['%name%' => $setting->getName()]);
                $this->publishConfirmMessage($request, $msg);

                return $this->redirect($this->generateUrl('settingsHomePage'));
            } catch (EntityAlreadyExistsException $e) {
                $form->addError(new FormError($e->getMessage()));
            }
        }

        $this->assignation['form'] = $form->createView();

        return $this->render('settings/add.html.twig', $this->assignation);
    }

    /**
     * Return an deletion form for requested setting.
     *
     * @param Request $request
     * @param int     $settingId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $settingId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_SETTINGS');

        /** @var Setting|null $setting */
        $setting = $this->get('em')->find(Setting::class, (int) $settingId);

        if (null !== $setting) {
            $this->assignation['setting'] = $setting;

            $form = $this->createForm(FormType::class, $setting);
            $form->handleRequest($request);

            if ($form->isValid()) {
                $this->get('em')->remove($setting);
                $this->get('em')->flush();

                /** @var CacheProvider $cacheDriver */
                $cacheDriver = $this->get('em')->getConfiguration()->getResultCacheImpl();
                if ($cacheDriver !== null) {
                    $cacheDriver->deleteAll();
                }

                $msg = $this->getTranslator()->trans('setting.%name%.deleted', ['%name%' => $setting->getName()]);
                $this->publishConfirmMessage($request, $msg);

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl('settingsHomePage'));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('settings/delete.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }
}
