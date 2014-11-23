<?php
/**
 * Copyright REZO ZERO 2014
 *
 * @file SettingsController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\Setting;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\ListManagers\EntityListManager;
use Themes\Rozier\RozierApp;

use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use \Symfony\Component\Form\Form;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
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
        /*
         * Manage get request to filter list
         */
        $listManager = new EntityListManager(
            $request,
            $this->getService('em'),
            'RZ\Roadiz\Core\Entities\Setting',
            array(),
            array('name'=>'ASC')
        );
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $settings = $listManager->getEntities();

        $this->assignation['settings'] = array();

        foreach ($settings as $setting) {
            $form = $this->buildShortEditForm($setting);
            $form->handleRequest();
            if ($form->isValid() &&
                $form->getData()['id'] == $setting->getId()) {
                try {
                    $this->editSetting($form->getData(), $setting);
                    $msg = $this->getTranslator()->trans('setting.%name%.updated', array('%name%'=>$setting->getName()));
                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getService('logger')->info($msg);
                } catch (EntityAlreadyExistsException $e) {
                    $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                    $this->getService('logger')->warning($e->getMessage());
                }
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate(
                        'settingsHomePage'
                    )
                );
                $response->prepare($request);

                return $response->send();
            }
            $this->assignation['settings'][] = array(
                'setting' => $setting,
                'form' => $form->createView()
            );
        }

        return new Response(
            $this->getTwig()->render('settings/list.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * [byGroupAction description]
     *
     * @param  Request $request        [description]
     * @param  [type]  $settingGroupId [description]
     *
     * @return [type]                  [description]
     */
    public function byGroupAction(Request $request, $settingGroupId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_SETTINGS');

        $settingGroup = $this->getService('em')
            ->find('RZ\Roadiz\Core\Entities\SettingGroup', (int) $settingGroupId);

        if ($settingGroup !== null) {
            $this->assignation['settingGroup'] = $settingGroup;

            /*
             * Manage get request to filter list
             */
            $listManager = new EntityListManager(
                $request,
                $this->getService('em'),
                'RZ\Roadiz\Core\Entities\Setting',
                array('settingGroup'=>$settingGroup),
                array('name'=>'ASC')
            );
            $listManager->handle();

            $this->assignation['filters'] = $listManager->getAssignation();
            $settings = $listManager->getEntities();
            $this->assignation['settings'] = array();

            foreach ($settings as $setting) {
                $form = $this->buildShortEditForm($setting);
                $form->handleRequest();
                if ($form->isValid() &&
                    $form->getData()['id'] == $setting->getId()) {
                    try {
                        $this->editSetting($form->getData(), $setting);
                        $msg = $this->getTranslator()->trans('setting.%name%.updated', array('%name%'=>$setting->getName()));
                        $request->getSession()->getFlashBag()->add('confirm', $msg);
                        $this->getService('logger')->info($msg);
                    } catch (EntityAlreadyExistsException $e) {
                        $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                        $this->getService('logger')->warning($e->getMessage());
                    }
                    /*
                     * Force redirect to avoid resending form when refreshing page
                     */
                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'settingGroupsSettingsPage',
                            array('settingGroupId' => $settingGroupId)
                        )
                    );
                    $response->prepare($request);

                    return $response->send();
                }
                $this->assignation['settings'][] = array(
                    'setting' => $setting,
                    'form' => $form->createView()
                );
            }

            return new Response(
                $this->getTwig()->render('settings/list.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );

        } else {
            return $this->throw404();
        }
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
                    $msg = $this->getTranslator()->trans('setting.%name%.updated', array('%name%'=>$setting->getName()));
                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getService('logger')->info($msg);
                } catch (EntityAlreadyExistsException $e) {
                    $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                    $this->getService('logger')->warning($e->getMessage());
                }
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate(
                        'settingsEditPage',
                        array('settingId' => $setting->getId())
                    )
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('settings/edit.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
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
        // if (!($this->getSecurityContext()->isGranted('ROLE_ACCESS_SETTINGS')
        //     || $this->getSecurityContext()->isGranted('ROLE_SUPERADMIN')))
        //     return $this->throw404();

        $setting = new Setting();

        if (null !== $setting) {
            $this->assignation['setting'] = $setting;

            $form = $this->buildAddForm($setting);

            $form->handleRequest();

            if ($form->isValid()) {

                try {
                    $this->addSetting($form->getData(), $setting);
                    $msg = $this->getTranslator()->trans('setting.%name%.created', array('%name%'=>$setting->getName()));
                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getService('logger')->info($msg);

                } catch (EntityAlreadyExistsException $e) {
                    $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                    $this->getService('logger')->warning($e->getMessage());
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
                array('content-type' => 'text/html')
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

                $msg = $this->getTranslator()->trans('setting.%name%.deleted', array('%name%'=>$setting->getName()));
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getService('logger')->info($msg);

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
                array('content-type' => 'text/html')
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
                throw new EntityAlreadyExistsException($this->getTranslator()->trans('setting.%name%.no_update.already_exists', array('%name%'=>$setting->getName())), 1);
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
                throw new EntityAlreadyExistsException($this->getTranslator()->trans('setting.%name%.no_update.already_exists', array('%name%'=>$setting->getName())), 1);
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
            throw new EntityAlreadyExistsException($this->getTranslator()->trans('setting.%name%.no_creation.already_exists', array('%name%'=>$setting->getName())), 1);
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
            throw new EntityAlreadyExistsException($this->getTranslator()->trans('setting.%name%.no_creation.already_exists', array('%name%'=>$setting->getName())), 1);
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
        $defaults = array(
            'name' =>    $setting->getName(),
            'value' =>   $setting->getValue(),
            'visible' => $setting->isVisible(),
            'type' =>    $setting->getType()
        );
        $builder = $this->getService('formFactory')
            ->createBuilder('form', $defaults)
            ->add('name', 'text', array(
                'label' => $this->getTranslator()->trans('name'),
                'constraints' => array(
                    new NotBlank()
                )
            ))
            ->add('value', NodeTypeField::$typeToForm[$setting->getType()], array(
                'label' => $this->getTranslator()->trans('value'),
                'required' => false
            ))
            ->add('visible', 'checkbox', array(
                'label' => $this->getTranslator()->trans('visible'),
                'required' => false
            ))
            ->add('type', 'choice', array(
                'label' => $this->getTranslator()->trans('type'),
                'required' => true,
                'choices' => NodeTypeField::$typeToHuman
            ))
            ->add(
                'settingGroup',
                new \RZ\Roadiz\CMS\Forms\SettingGroupType(),
                array(
                    'label' => $this->getTranslator()->trans('setting.group')
                )
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
        $defaults = array(
            'id' =>      $setting->getId(),
            'name' =>    $setting->getName(),
            'value' =>   $setting->getValue(),
            'visible' => $setting->isVisible(),
            'type' =>    $setting->getType()
        );

        if (null !== $setting->getSettingGroup()) {
            $defaults['settingGroup'] = $setting->getSettingGroup()->getId();
        }

        $builder = $this->getService('formFactory')
            ->createBuilder('form', $defaults)
            ->add(
                'name',
                'text',
                array(
                    'label' => $this->getTranslator()->trans('name'),
                    'constraints' => array(new NotBlank())
                )
            )
            ->add(
                'id',
                'hidden',
                array(
                    'data'=>$setting->getId(),
                    'required' => true
                )
            )
            ->add(
                'value',
                NodeTypeField::$typeToForm[$setting->getType()],
                static::getFormOptionsForSetting($setting, $this->getTranslator())
            )
            ->add(
                'visible',
                'checkbox',
                array(
                    'label' => $this->getTranslator()->trans('visible'),
                    'required' => false
                )
            )
            ->add(
                'type',
                'choice',
                array(
                    'label' => $this->getTranslator()->trans('type'),
                    'required' => true,
                    'choices' => NodeTypeField::$typeToHuman
                )
            )
            ->add(
                'settingGroup',
                new \RZ\Roadiz\CMS\Forms\SettingGroupType(),
                array(
                    'label' => $this->getTranslator()->trans('setting.group')
                )
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
        $defaults = array(
            'id' =>      $setting->getId(),
            'value' =>   $setting->getValue()
        );
        $builder = $this->getService('formFactory')
            ->createBuilder('form', $defaults)
            ->add('id', 'hidden', array(
                'data'=>$setting->getId(),
                'required' => true
            ))
            ->add('value', NodeTypeField::$typeToForm[$setting->getType()],
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
            ->add('settingId', 'hidden', array(
                'data' => $setting->getId(),
                'constraints' => array(
                    new NotBlank()
                )
            ));

        return $builder->getForm();
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public static function getSettings()
    {
        return $this->getService('em')
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
                return array(
                    'label' => $label,
                    'empty_value' => $translator->trans('choose.value'),
                    'required' => false
                );
            case NodeTypeField::DATETIME_T:
                return array(
                    'label' => $label,
                    'years' => range(date('Y')-10, date('Y')+10),
                    'required' => false
                );
            case NodeTypeField::INTEGER_T:
                return array(
                    'label' => $label,
                    'required' => false,
                    'constraints' => array(
                        new Type('integer')
                    )
                );
            case NodeTypeField::DECIMAL_T:
                return array(
                    'label' => $label,
                    'required' => false,
                    'constraints' => array(
                        new Type('double')
                    )
                );
            case NodeTypeField::COLOUR_T:
                return array(
                    'label' => $label,
                    'required' => false,
                    'attr' => array(
                        'class' => 'colorpicker-input'
                    )
                );

            default:
                return array(
                    'label' => $label,
                    'required' => false
                );
        }
    }
}
