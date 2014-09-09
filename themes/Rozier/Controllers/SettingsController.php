<?php
/**
 * Copyright REZO ZERO 2014
 *
 * @file SettingsController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */

namespace Themes\Rozier\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Setting;
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
        /*
         * Manage get request to filter list
         */
        $listManager = new EntityListManager(
            $request,
            $this->getKernel()->em(),
            'RZ\Renzo\Core\Entities\Setting',
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
                    $msg = $this->getTranslator()->trans('setting.updated', array('%name%'=>$setting->getName()));
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
                    $this->getKernel()->getUrlGenerator()->generate(
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
     * Return an edition form for requested setting.
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $settingId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $settingId)
    {
        $setting = $this->getKernel()->em()
            ->find('RZ\Renzo\Core\Entities\Setting', (int) $settingId);

        if ($setting !== null) {
            $this->assignation['setting'] = $setting;

            $form = $this->buildEditForm($setting);
            $form->handleRequest();

            if ($form->isValid()) {
                try {
                    $this->editSetting($form->getData(), $setting);
                    $msg = $this->getTranslator()->trans('setting.updated', array('%name%'=>$setting->getName()));
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
                    $this->getKernel()->getUrlGenerator()->generate(
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
        $setting = new Setting();

        if (null !== $setting) {
            $this->assignation['setting'] = $setting;

            $form = $this->buildAddForm($setting);

            $form->handleRequest();

            if ($form->isValid()) {

                try {
                    $this->addSetting($form->getData(), $setting);
                    $msg = $this->getTranslator()->trans('setting.created', array('%name%'=>$setting->getName()));
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
                    $this->getKernel()->getUrlGenerator()->generate('settingsHomePage')
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
        $setting = $this->getKernel()->em()
            ->find('RZ\Renzo\Core\Entities\Setting', (int) $settingId);

        if (null !== $setting) {
            $this->assignation['setting'] = $setting;

            $form = $this->buildDeleteForm($setting);

            $form->handleRequest();

            if ($form->isValid() &&
                $form->getData()['settingId'] == $setting->getId() ) {

                $this->deleteSetting($form->getData(), $setting);

                $msg = $this->getTranslator()->trans('setting.deleted', array('%name%'=>$setting->getName()));
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getLogger()->info($msg);

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getKernel()->getUrlGenerator()->generate('settingsHomePage')
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
     * @param RZ\Renzo\Core\Entities\Setting $setting
     *
     * @return boolean
     */
    private function editSetting($data, Setting $setting)
    {
        if ($data['id'] == $setting->getId()) {
            unset($data['id']);

            if (isset($data['name']) &&
                $data['name'] != $setting->getName() &&
                $this->getKernel()->em()
                ->getRepository('RZ\Renzo\Core\Entities\Setting')
                ->exists($data['name'])) {
                throw new EntityAlreadyExistsException($this->getTranslator()->trans('setting.no_update.already_exists', array('%name%'=>$setting->getName())), 1);
            }
            //try {
                foreach ($data as $key => $value) {
                    if ($key != 'group') {
                        $setter = 'set'.ucwords($key);
                        $setting->$setter( $value );
                    }
                    else {
                        $group = $this->getKernel()->em()
                                 ->find('RZ\Renzo\Core\Entities\SettingGroup', (int) $value);
                        $setting->setSettingGroup($group);
                    }
                }

                $this->getKernel()->em()->flush();

                return true;
            // } catch (\Exception $e) {
            //     throw new EntityAlreadyExistsException($this->getTranslator()->trans('setting.no_update.already_exists', array('%name%'=>$setting->getName())), 1);
            // }
        }
    }

    /**
     * @param array                          $data
     * @param RZ\Renzo\Core\Entities\Setting $setting
     *
     * @return boolean
     */
    private function addSetting($data, Setting $setting)
    {
        if ($this->getKernel()->em()
            ->getRepository('RZ\Renzo\Core\Entities\Setting')
            ->exists($data['name'])) {
            throw new EntityAlreadyExistsException($this->getTranslator()->trans('setting.no_creation.already_exists', array('%name%'=>$setting->getName())), 1);
        }

        try {
            foreach ($data as $key => $value) {
                $setter = 'set'.ucwords($key);
                $setting->$setter( $value );
            }

            $this->getKernel()->em()->persist($setting);
            $this->getKernel()->em()->flush();

            return true;
        } catch (\Exception $e) {
            throw new EntityAlreadyExistsException($this->getTranslator()->trans('setting.no_creation.already_exists', array('%name%'=>$setting->getName())), 1);
        }
    }

    /**
     * @param array                          $data
     * @param RZ\Renzo\Core\Entities\Setting $setting
     *
     * @return boolean
     */
    private function deleteSetting($data, Setting $setting)
    {
        $this->getKernel()->em()->remove($setting);
        $this->getKernel()->em()->flush();

        return true;
    }

    /**
     * @param RZ\Renzo\Core\Entities\Setting $setting
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildAddForm(Setting $setting)
    {
        $defaults = array(
            'name' =>    $setting->getName(),
            'Value' =>   $setting->getValue(),
            'visible' => $setting->isVisible(),
            'type' =>    $setting->getType(),
        );
        $builder = $this->getFormFactory()
            ->createBuilder('form', $defaults)
            ->add('name', 'text', array(
                'constraints' => array(
                    new NotBlank()
                )
            ))
            ->add('Value', NodeTypeField::$typeToForm[$setting->getType()], array('required' => false))
            ->add('visible', 'checkbox', array('required' => false))
            ->add('type', 'choice', array(
                'required' => true,
                'choices' => NodeTypeField::$typeToHuman
            ));

        return $builder->getForm();
    }


    /**
     * @param RZ\Renzo\Core\Entities\Setting $setting
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildEditForm(Setting $setting)
    {
        $defaults = array(
            'id' =>      $setting->getId(),
            'name' =>    $setting->getName(),
            'Value' =>   $setting->getValue(),
            'visible' => $setting->isVisible(),
            'type' =>    $setting->getType(),
            'group' =>   $setting->getSettingGroup()->getId(),
        );
        $builder = $this->getFormFactory()
            ->createBuilder('form', $defaults)
            ->add('name', 'text', array(
                'constraints' => array(
                    new NotBlank()
                )
            ))
            ->add('id', 'hidden', array(
                'data'=>$setting->getId(),
                'required' => true
            ))
            ->add('Value', NodeTypeField::$typeToForm[$setting->getType()], array('required' => false))
            ->add('visible', 'checkbox', array('required' => false))
            ->add('type', 'choice', array(
                'required' => true,
                'choices' => NodeTypeField::$typeToHuman
            ))
            ->add('group', new \RZ\Renzo\CMS\Forms\SettingGroupType()
            );

        return $builder->getForm();
    }

    /**
     * @param RZ\Renzo\Core\Entities\Setting $setting
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildShortEditForm(Setting $setting)
    {
        $defaults = array(
            'id' =>      $setting->getId(),
            'Value' =>   $setting->getValue()
        );
        $builder = $this->getFormFactory()
            ->createBuilder('form', $defaults)
            ->add('id', 'hidden', array(
                'data'=>$setting->getId(),
                'required' => true
            ))
            ->add('Value', NodeTypeField::$typeToForm[$setting->getType()], array(
                'label' => false,
                'required' => false
            ));

        return $builder->getForm();
    }

    /**
     * @param RZ\Renzo\Core\Entities\Setting $setting
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildDeleteForm(Setting $setting)
    {
        $builder = $this->getFormFactory()
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
        return $this->getKernel()->em()
            ->getRepository('RZ\Renzo\Core\Entities\Setting')
            ->findAll();
    }
}