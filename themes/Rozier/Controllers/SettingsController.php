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

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\Setting;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\ListManagers\EntityListManager;
use Themes\Rozier\RozierApp;

use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Translation\Translator;

/**
* Settings controller
*/
class SettingsController extends RozierApp
{

    /**
     * List every settings.
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_SETTINGS');

        if (null !== $response = $this->commonSettingList($request)) {
            return $response->send();
        }

        return new Response(
            $this->getTwig()->render('settings/list.html.twig', $this->assignation),
            Response::HTTP_OK,
            ['content-type' => 'text/html']
        );
    }

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $settingGroupId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function byGroupAction(Request $request, $settingGroupId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_SETTINGS');

        $settingGroup = $this->getService('em')
            ->find('RZ\Roadiz\Core\Entities\SettingGroup', (int) $settingGroupId);

        if ($settingGroup !== null) {
            $this->assignation['settingGroup'] = $settingGroup;

            if (null !== $response = $this->commonSettingList($request, $settingGroup)) {
                return $response->send();
            }

            return new Response(
                $this->getTwig()->render('settings/list.html.twig', $this->assignation),
                Response::HTTP_OK,
                ['content-type' => 'text/html']
            );

        } else {
            return $this->throw404();
        }
    }

    protected function commonSettingList(Request $request, $settingGroup = null)
    {
        $criteria = [];
        if (null !== $settingGroup) {
            $criteria = ['settingGroup'=>$settingGroup];
        }
        /*
         * Manage get request to filter list
         */
        $listManager = new EntityListManager(
            $request,
            $this->getService('em'),
            'RZ\Roadiz\Core\Entities\Setting',
            $criteria,
            ['name'=>'ASC']
        );
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $settings = $listManager->getEntities();
        $this->assignation['settings'] = [];

        foreach ($settings as $setting) {
            $form = $this->buildShortEditForm($setting);
            $form->handleRequest();
            if ($form->isValid() &&
                $form->getData()['id'] == $setting->getId()) {
                try {
                    $this->editSetting($form->getData(), $setting);
                    $msg = $this->getTranslator()->trans(
                        'setting.%name%.updated',
                        ['%name%'=>$setting->getName()]
                    );
                    $this->publishConfirmMessage($request, $msg);
                } catch (EntityAlreadyExistsException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                }


                if (null !== $settingGroup) {
                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'settingGroupsSettingsPage',
                            ['settingGroupId' => $settingGroup->getId()]
                        )
                    );
                } else {
                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'settingsHomePage'
                        )
                    );
                }

                $response->prepare($request);

                return $response;
            }
            $this->assignation['settings'][] = [
                'setting' => $setting,
                'form' => $form->createView()
            ];
        }

        return null;
    }


    /**
     * Return an edition form for requested setting.
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $settingId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $settingId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_SETTINGS');

        $setting = $this->getService('em')
            ->find('RZ\Roadiz\Core\Entities\Setting', (int) $settingId);

        if ($setting !== null) {
            $this->assignation['setting'] = $setting;

            $form = $this->buildEditForm($setting);
            $form->handleRequest();

            if ($form->isValid()) {
                try {
                    $this->editSetting($form->getData(), $setting);
                    $msg = $this->getTranslator()->trans('setting.%name%.updated', ['%name%'=>$setting->getName()]);
                    $this->publishConfirmMessage($request, $msg);
                } catch (EntityAlreadyExistsException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                }
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate(
                        'settingsEditPage',
                        ['settingId' => $setting->getId()]
                    )
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('settings/edit.html.twig', $this->assignation),
                Response::HTTP_OK,
                ['content-type' => 'text/html']
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return an creation form for requested setting.
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_SETTINGS');

        $setting = new Setting();

        if (null !== $setting) {
            $this->assignation['setting'] = $setting;

            $form = $this->buildAddForm($setting);

            $form->handleRequest();

            if ($form->isValid()) {
                try {
                    $this->addSetting($form->getData(), $setting);
                    $msg = $this->getTranslator()->trans('setting.%name%.created', ['%name%'=>$setting->getName()]);
                    $this->publishConfirmMessage($request, $msg);

                } catch (EntityAlreadyExistsException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                }

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate('settingsHomePage')
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('settings/add.html.twig', $this->assignation),
                Response::HTTP_OK,
                ['content-type' => 'text/html']
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return an deletion form for requested setting.
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $settingId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $settingId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_SETTINGS');

        $setting = $this->getService('em')
            ->find('RZ\Roadiz\Core\Entities\Setting', (int) $settingId);

        if (null !== $setting) {
            $this->assignation['setting'] = $setting;

            $form = $this->buildDeleteForm($setting);

            $form->handleRequest();

            if ($form->isValid() &&
                $form->getData()['settingId'] == $setting->getId() ) {
                $this->deleteSetting($form->getData(), $setting);

                $msg = $this->getTranslator()->trans('setting.%name%.deleted', ['%name%'=>$setting->getName()]);
                $this->publishConfirmMessage($request, $msg);

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate('settingsHomePage')
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('settings/delete.html.twig', $this->assignation),
                Response::HTTP_OK,
                ['content-type' => 'text/html']
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * @param array                          $data
     * @param RZ\Roadiz\Core\Entities\Setting $setting
     *
     * @return boolean
     */
    private function editSetting($data, Setting $setting)
    {
        if ($data['id'] == $setting->getId()) {
            unset($data['id']);

            if (isset($data['name']) &&
                $data['name'] != $setting->getName() &&
                $this->getService('em')
                     ->getRepository('RZ\Roadiz\Core\Entities\Setting')
                     ->exists($data['name'])) {
                throw new EntityAlreadyExistsException($this->getTranslator()->trans('setting.%name%.no_update.already_exists', ['%name%'=>$setting->getName()]), 1);
            }
            try {
                foreach ($data as $key => $value) {
                    if ($key != 'settingGroup') {
                        $setter = 'set'.ucwords($key);
                        $setting->$setter( $value );
                    } else {
                        $group = $this->getService('em')
                                 ->find('RZ\Roadiz\Core\Entities\SettingGroup', (int) $value);
                        $setting->setSettingGroup($group);
                    }
                }

                $this->getService('em')->flush();

                // Clear result cache
                $cacheDriver = Kernel::getService('em')->getConfiguration()->getResultCacheImpl();
                if ($cacheDriver !== null) {
                    $cacheDriver->deleteAll();
                }

                return true;
            } catch (\Exception $e) {
                throw new EntityAlreadyExistsException($this->getTranslator()->trans('setting.%name%.no_update.already_exists', ['%name%'=>$setting->getName()]), 1);
            }
        }
    }

    /**
     * @param array                          $data
     * @param RZ\Roadiz\Core\Entities\Setting $setting
     *
     * @return boolean
     */
    private function addSetting($data, Setting $setting)
    {
        if ($this->getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Setting')
            ->exists($data['name'])) {
            throw new EntityAlreadyExistsException($this->getTranslator()->trans('setting.%name%.no_creation.already_exists', ['%name%'=>$setting->getName()]), 1);
        }

        try {
            foreach ($data as $key => $value) {
                if ($key != 'settingGroup') {
                    $setter = 'set'.ucwords($key);
                    $setting->$setter( $value );
                } else {
                    $group = $this->getService('em')
                             ->find('RZ\Roadiz\Core\Entities\SettingGroup', (int) $value);
                    $setting->setSettingGroup($group);
                }
            }

            $this->getService('em')->persist($setting);
            $this->getService('em')->flush();

            return true;
        } catch (\Exception $e) {
            throw new EntityAlreadyExistsException($this->getTranslator()->trans('setting.%name%.no_creation.already_exists', ['%name%'=>$setting->getName()]), 1);
        }
    }

    /**
     * @param array                          $data
     * @param RZ\Roadiz\Core\Entities\Setting $setting
     *
     * @return boolean
     */
    private function deleteSetting($data, Setting $setting)
    {
        $this->getService('em')->remove($setting);
        $this->getService('em')->flush();

        return true;
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Setting $setting
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildAddForm(Setting $setting)
    {
        $defaults = [
            'name' =>    $setting->getName(),
            'value' =>   $setting->getValue(),
            'visible' => $setting->isVisible(),
            'type' =>    $setting->getType()
        ];
        $builder = $this->getService('formFactory')
            ->createBuilder('form', $defaults)
            ->add('name', 'text', [
                'label' => $this->getTranslator()->trans('name'),
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('value', NodeTypeField::$typeToForm[$setting->getType()], [
                'label' => $this->getTranslator()->trans('value'),
                'required' => false
            ])
            ->add('visible', 'checkbox', [
                'label' => $this->getTranslator()->trans('visible'),
                'required' => false
            ])
            ->add('type', 'choice', [
                'label' => $this->getTranslator()->trans('type'),
                'required' => true,
                'choices' => NodeTypeField::$typeToHuman
            ])
            ->add(
                'settingGroup',
                new \RZ\Roadiz\CMS\Forms\SettingGroupType(),
                [
                    'label' => $this->getTranslator()->trans('setting.group')
                ]
            );

        return $builder->getForm();
    }


    /**
     * @param RZ\Roadiz\Core\Entities\Setting $setting
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildEditForm(Setting $setting)
    {
        $defaults = [
            'id' =>      $setting->getId(),
            'name' =>    $setting->getName(),
            'value' =>   $setting->getValue(),
            'visible' => $setting->isVisible(),
            'type' =>    $setting->getType()
        ];

        if (null !== $setting->getSettingGroup()) {
            $defaults['settingGroup'] = $setting->getSettingGroup()->getId();
        }

        $builder = $this->getService('formFactory')
            ->createBuilder('form', $defaults)
            ->add(
                'name',
                'text',
                [
                    'label' => $this->getTranslator()->trans('name'),
                    'constraints' => [new NotBlank()]
                ]
            )
            ->add(
                'id',
                'hidden',
                [
                    'data'=>$setting->getId(),
                    'required' => true
                ]
            )
            ->add(
                'value',
                NodeTypeField::$typeToForm[$setting->getType()],
                static::getFormOptionsForSetting($setting, $this->getTranslator())
            )
            ->add(
                'visible',
                'checkbox',
                [
                    'label' => $this->getTranslator()->trans('visible'),
                    'required' => false
                ]
            )
            ->add(
                'type',
                'choice',
                [
                    'label' => $this->getTranslator()->trans('type'),
                    'required' => true,
                    'choices' => NodeTypeField::$typeToHuman
                ]
            )
            ->add(
                'settingGroup',
                new \RZ\Roadiz\CMS\Forms\SettingGroupType(),
                [
                    'label' => $this->getTranslator()->trans('setting.group')
                ]
            );

        return $builder->getForm();
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Setting $setting
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildShortEditForm(Setting $setting)
    {
        $defaults = [
            'id' =>      $setting->getId(),
            'value' =>   $setting->getValue()
        ];
        $builder = $this->getService('formFactory')
            ->createBuilder('form', $defaults)
            ->add('id', 'hidden', [
                'data'=>$setting->getId(),
                'required' => true
            ])
            ->add(
                'value',
                NodeTypeField::$typeToForm[$setting->getType()],
                static::getFormOptionsForSetting($setting, $this->getTranslator(), true)
            );

        return $builder->getForm();
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Setting $setting
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildDeleteForm(Setting $setting)
    {
        $builder = $this->getService('formFactory')
            ->createBuilder('form')
            ->add('settingId', 'hidden', [
                'data' => $setting->getId(),
                'constraints' => [
                    new NotBlank()
                ]
            ]);

        return $builder->getForm();
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public static function getSettings()
    {
        return Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Setting')
            ->findAll();
    }

    public static function getFormOptionsForSetting(
        $setting,
        Translator $translator,
        $shortEdit = false
    ) {

        $label = (!$shortEdit) ? $translator->trans('value') : false;

        switch ($setting->getType()) {
            case NodeTypeField::ENUM_T:
                return [
                    'label' => $label,
                    'empty_value' => $translator->trans('choose.value'),
                    'required' => false
                ];
            case NodeTypeField::DATETIME_T:
                return [
                    'label' => $label,
                    'years' => range(date('Y')-10, date('Y')+10),
                    'required' => false
                ];
            case NodeTypeField::INTEGER_T:
                return [
                    'label' => $label,
                    'required' => false,
                    'constraints' => [
                        new Type('integer')
                    ]
                ];
            case NodeTypeField::DECIMAL_T:
                return [
                    'label' => $label,
                    'required' => false,
                    'constraints' => [
                        new Type('double')
                    ]
                ];
            case NodeTypeField::COLOUR_T:
                return [
                    'label' => $label,
                    'required' => false,
                    'attr' => [
                        'class' => 'colorpicker-input'
                    ]
                ];

            default:
                return [
                    'label' => $label,
                    'required' => false
                ];
        }
    }
}
