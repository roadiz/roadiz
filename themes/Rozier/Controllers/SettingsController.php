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

use RZ\Roadiz\Core\Bags\SettingsBag;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Entities\Setting;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Themes\Rozier\RozierApp;

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

        return $this->render('settings/list.html.twig', $this->assignation);
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

            return $this->render('settings/list.html.twig', $this->assignation);

        } else {
            return $this->throw404();
        }
    }

    protected function commonSettingList(Request $request, $settingGroup = null)
    {
        $criteria = [];
        if (null !== $settingGroup) {
            $criteria = ['settingGroup' => $settingGroup];
        }
        /*
         * Manage get request to filter list
         */
        $listManager = $this->createEntityListManager(
            'RZ\Roadiz\Core\Entities\Setting',
            $criteria,
            ['name' => 'ASC']
        );
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $settings = $listManager->getEntities();
        $this->assignation['settings'] = [];

        foreach ($settings as $setting) {
            $form = $this->buildShortEditForm($setting);
            $form->handleRequest($request);
            if ($form->isValid() &&
                $form->getData()['id'] == $setting->getId()) {
                try {
                    $this->editSetting($form->getData(), $setting);
                    $msg = $this->getTranslator()->trans(
                        'setting.%name%.updated',
                        ['%name%' => $setting->getName()]
                    );
                    $this->publishConfirmMessage($request, $msg);
                } catch (EntityAlreadyExistsException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                }

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
            }

            $document = null;
            if ($setting->getType() == NodeTypeField::DOCUMENTS_T) {
                $document = SettingsBag::getDocument($setting->getName());
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
            $form->handleRequest($request);

            if ($form->isValid()) {
                try {
                    $this->editSetting($form->getData(), $setting);
                    $msg = $this->getTranslator()->trans('setting.%name%.updated', ['%name%' => $setting->getName()]);
                    $this->publishConfirmMessage($request, $msg);
                } catch (EntityAlreadyExistsException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                }
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl(
                    'settingsEditPage',
                    ['settingId' => $setting->getId()]
                ));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('settings/edit.html.twig', $this->assignation);
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

            $form->handleRequest($request);

            if ($form->isValid()) {
                try {
                    $this->addSetting($form->getData(), $setting);
                    $msg = $this->getTranslator()->trans('setting.%name%.created', ['%name%' => $setting->getName()]);
                    $this->publishConfirmMessage($request, $msg);

                } catch (EntityAlreadyExistsException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                }

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl('settingsHomePage'));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('settings/add.html.twig', $this->assignation);
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

            $form->handleRequest($request);

            if ($form->isValid() &&
                $form->getData()['settingId'] == $setting->getId()) {
                $this->deleteSetting($form->getData(), $setting);

                $msg = $this->getTranslator()->trans('setting.%name%.deleted', ['%name%' => $setting->getName()]);
                $this->publishConfirmMessage($request, $msg);

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl('settingsHomePage'));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('settings/delete.html.twig', $this->assignation);
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
                throw new EntityAlreadyExistsException($this->getTranslator()->trans('setting.%name%.no_update.already_exists', ['%name%' => $setting->getName()]), 1);
            }
            try {
                foreach ($data as $key => $value) {
                    if ($key == 'value') {
                        $this->setSettingValue($value, $setting);
                    } elseif ($key != 'settingGroup') {
                        $setter = 'set' . ucwords($key);
                        $setting->$setter($value);
                    } else {
                        $group = $this->getService('em')
                                      ->find('RZ\Roadiz\Core\Entities\SettingGroup', (int) $value);
                        $setting->setSettingGroup($group);
                    }
                }

                $this->getService('em')->flush();

                // Clear result cache
                $cacheDriver = $this->getService('em')->getConfiguration()->getResultCacheImpl();
                if ($cacheDriver !== null) {
                    $cacheDriver->deleteAll();
                }

                return true;
            } catch (\Exception $e) {
                throw new EntityAlreadyExistsException($this->getTranslator()->trans('setting.%name%.no_update.already_exists', ['%name%' => $setting->getName()]), 1);
            }
        }
    }

    /**
     * Set setting value according to its type.
     *
     * @param string  $value
     * @param Setting $setting
     */
    protected function setSettingValue($value, Setting $setting)
    {
        switch ($setting->getType()) {
            case NodeTypeField::DOCUMENTS_T:
                if ($value !== null &&
                    $value->getError() == UPLOAD_ERR_OK &&
                    $value->isValid()) {
                    $document = new Document();
                    $document->setFilename($value->getClientOriginalName());
                    $document->setMimeType($value->getMimeType());
                    $this->getService('em')->persist($document);
                    $this->getService('em')->flush();

                    $value->move(Document::getFilesFolder() . '/' . $document->getFolder(), $document->getFilename());

                    $setting->setValue($document->getId());
                }
                break;
            default:
                $setting->setValue($value);
                break;
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
            throw new EntityAlreadyExistsException($this->getTranslator()->trans('setting.%name%.no_creation.already_exists', ['%name%' => $setting->getName()]), 1);
        }

        try {
            foreach ($data as $key => $value) {
                if ($key == 'value') {
                    $this->setSettingValue($value, $setting);
                } elseif ($key != 'settingGroup') {
                    $setter = 'set' . ucwords($key);
                    $setting->$setter($value);
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
            throw new EntityAlreadyExistsException($this->getTranslator()->trans('setting.%name%.no_creation.already_exists', ['%name%' => $setting->getName()]), 1);
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
            'name' => $setting->getName(),
            'value' => $setting->getValue(),
            'visible' => $setting->isVisible(),
            'type' => $setting->getType(),
        ];

        $builder = $this->createFormBuilder($defaults)
                        ->add('name', 'text', [
                            'label' => 'name',
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ])
                        ->add('value', $this->getFormTypeFromSettingType($setting), [
                            'label' => 'value',
                            'required' => false,
                        ])
                        ->add('visible', 'checkbox', [
                            'label' => 'visible',
                            'required' => false,
                        ])
                        ->add('type', 'choice', [
                            'label' => 'type',
                            'required' => true,
                            'choices' => Setting::$typeToHuman,
                        ])
                        ->add(
                            'settingGroup',
                            new \RZ\Roadiz\CMS\Forms\SettingGroupType(),
                            [
                                'label' => 'setting.group',
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
            'id' => $setting->getId(),
            'name' => $setting->getName(),
            'value' => $setting->getValue(),
            'visible' => $setting->isVisible(),
            'type' => $setting->getType(),
        ];

        if ($setting->getType() == NodeTypeField::DOCUMENTS_T) {
            $defaults['value'] = null;
        }

        if (null !== $setting->getSettingGroup()) {
            $defaults['settingGroup'] = $setting->getSettingGroup()->getId();
        }

        $builder = $this->createFormBuilder($defaults)
                        ->add(
                            'name',
                            'text',
                            [
                                'label' => 'name',
                                'constraints' => [new NotBlank()],
                            ]
                        )
                        ->add(
                            'id',
                            'hidden',
                            [
                                'data' => $setting->getId(),
                                'required' => true,
                            ]
                        )
                        ->add(
                            'value',
                            $this->getFormTypeFromSettingType($setting),
                            static::getFormOptionsForSetting($setting, $this->getTranslator())
                        )
                        ->add(
                            'visible',
                            'checkbox',
                            [
                                'label' => 'visible',
                                'required' => false,
                            ]
                        )
                        ->add(
                            'type',
                            'choice',
                            [
                                'label' => 'type',
                                'required' => true,
                                'choices' => Setting::$typeToHuman,
                            ]
                        )
                        ->add(
                            'settingGroup',
                            new \RZ\Roadiz\CMS\Forms\SettingGroupType(),
                            [
                                'label' => 'setting.group',
                            ]
                        );

        return $builder->getForm();
    }

    /**
     *
     * @param  Setting $setting [description]
     * @return [type]           [description]
     */
    protected function getFormTypeFromSettingType(Setting $setting)
    {
        switch ($setting->getType()) {
            case NodeTypeField::JSON_T:
                return new \RZ\Roadiz\CMS\Forms\JsonType();
            case NodeTypeField::CSS_T:
                return new \RZ\Roadiz\CMS\Forms\CssType();
            case NodeTypeField::MARKDOWN_T:
                return new \RZ\Roadiz\CMS\Forms\MarkdownType();

            default:
                return Setting::$typeToForm[$setting->getType()];
        }
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Setting $setting
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildShortEditForm(Setting $setting)
    {
        $defaults = [
            'id' => $setting->getId(),
            'value' => $setting->getValue(),
        ];

        if ($setting->getType() == NodeTypeField::DOCUMENTS_T) {
            $defaults['value'] = null;
        }
        $builder = $this->createFormBuilder($defaults)
                        ->add('id', 'hidden', [
                            'data' => $setting->getId(),
                            'required' => true,
                        ])
                        ->add(
                            'value',
                            $this->getFormTypeFromSettingType($setting),
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
        $builder = $this->createFormBuilder()
                        ->add('settingId', 'hidden', [
                            'data' => $setting->getId(),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ]);

        return $builder->getForm();
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
                    'placeholder' => $translator->trans('choose.value'),
                    'required' => false,
                ];
            case NodeTypeField::DATETIME_T:
                return [
                    'label' => $label,
                    'years' => range(date('Y') - 10, date('Y') + 10),
                    'required' => false,
                ];
            case NodeTypeField::INTEGER_T:
                return [
                    'label' => $label,
                    'required' => false,
                    'constraints' => [
                        new Type('integer'),
                    ],
                ];
                    case NodeTypeField::DECIMAL_T:
                return [
                    'label' => $label,
                    'required' => false,
                    'constraints' => [
                        new Type('double'),
                    ],
                ];
                    case NodeTypeField::COLOUR_T:
                return [
                    'label' => $label,
                    'required' => false,
                    'attr' => [
                        'class' => 'colorpicker-input',
                    ],
                ];

                    default:
                return [
                    'label' => $label,
                    'required' => false,
                ];
        }
    }
}
