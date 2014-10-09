<?php
/**
 * Copyright REZO ZERO 2014
 *
 * @file SettingGroupsController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */

namespace Themes\Rozier\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\SettingGroup;
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
* SettingGroups controller
*/
class SettingGroupsController extends RozierApp
{

    /**
     * List every settingGroups.
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
            'RZ\Renzo\Core\Entities\SettingGroup',
            array(),
            array('name'=>'ASC')
        );
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['settingGroups'] = $listManager->getEntities();

        return new Response(
            $this->getTwig()->render('settingGroups/list.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * Return an edition form for requested settingGroup.
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $settingGroupId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $settingGroupId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_SETTINGS');

        $settingGroup = $this->getService('em')
            ->find('RZ\Renzo\Core\Entities\SettingGroup', (int) $settingGroupId);

        if ($settingGroup !== null) {
            $this->assignation['settingGroup'] = $settingGroup;

            $form = $this->buildEditForm($settingGroup);
            $form->handleRequest();

            if ($form->isValid()) {
                try {
                    $this->editSettingGroup($form->getData(), $settingGroup);
                    $msg = $this->getTranslator()->trans(
                        'settingGroup.%name%.updated',
                        array('%name%'=>$settingGroup->getName())
                    );
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
                        'settingGroupsEditPage',
                        array('settingGroupId' => $settingGroup->getId())
                    )
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('settingGroups/edit.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return an creation form for requested settingGroup.
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_SETTINGS');

        $settingGroup = new SettingGroup();

        if (null !== $settingGroup) {
            $this->assignation['settingGroup'] = $settingGroup;

            $form = $this->buildAddForm($settingGroup);

            $form->handleRequest();

            if ($form->isValid()) {

                try {
                    $this->addSettingGroup($form->getData(), $settingGroup);
                    $msg = $this->getTranslator()->trans(
                        'settingGroup.%name%.created',
                        array('%name%'=>$settingGroup->getName())
                    );
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
                    $this->getService('urlGenerator')->generate('settingGroupsHomePage')
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('settingGroups/add.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return an deletion form for requested settingGroup.
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $settingGroupId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $settingGroupId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_SETTINGS');

        $settingGroup = $this->getService('em')
            ->find('RZ\Renzo\Core\Entities\SettingGroup', (int) $settingGroupId);

        if (null !== $settingGroup) {
            $this->assignation['settingGroup'] = $settingGroup;

            $form = $this->buildDeleteForm($settingGroup);

            $form->handleRequest();

            if ($form->isValid() &&
                $form->getData()['settingGroupId'] == $settingGroup->getId() ) {

                $this->deleteSettingGroup($form->getData(), $settingGroup);

                $msg = $this->getTranslator()->trans(
                    'settingGroup.%name%.deleted',
                    array('%name%'=>$settingGroup->getName())
                );
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getService('logger')->info($msg);

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate('settingGroupsHomePage')
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('settingGroups/delete.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * @param array                          $data
     * @param RZ\Renzo\Core\Entities\SettingGroup $settingGroup
     *
     * @return boolean
     */
    private function editSettingGroup($data, SettingGroup $settingGroup)
    {
        if ($data['id'] == $settingGroup->getId()) {
            unset($data['id']);

            if (isset($data['name']) &&
                $data['name'] != $settingGroup->getName() &&
                $this->getService('em')
                ->getRepository('RZ\Renzo\Core\Entities\SettingGroup')
                ->exists($data['name'])) {
                throw new EntityAlreadyExistsException($this->getTranslator()->trans(
                    'settingGroup.%name%.no_update.already_exists',
                    array('%name%'=>$settingGroup->getName())
                ), 1);
            }
            try {
                foreach ($data as $key => $value) {
                    if ($key != 'group') {
                        $setter = 'set'.ucwords($key);
                        $settingGroup->$setter( $value );
                    } else {
                        $group = $this->getService('em')
                                 ->find('RZ\Renzo\Core\Entities\SettingGroupGroup', (int) $value);
                        $settingGroup->setSettingGroupGroup($group);
                    }
                }

                $this->getService('em')->flush();

                return true;
            } catch (\Exception $e) {
                throw new EntityAlreadyExistsException($this->getTranslator()->trans(
                    'settingGroup.%name%.no_update.already_exists',
                    array('%name%'=>$settingGroup->getName())
                ), 1);
            }
        }
    }

    /**
     * @param array                          $data
     * @param RZ\Renzo\Core\Entities\SettingGroup $settingGroup
     *
     * @return boolean
     */
    private function addSettingGroup($data, SettingGroup $settingGroup)
    {
        if ($this->getService('em')
            ->getRepository('RZ\Renzo\Core\Entities\SettingGroup')
            ->exists($data['name'])) {
            throw new EntityAlreadyExistsException($this->getTranslator()->trans(
                'settingGroup.%name%.no_creation.already_exists',
                array('%name%'=>$settingGroup->getName())
            ), 1);
        }

        try {
            foreach ($data as $key => $value) {
                $setter = 'set'.ucwords($key);
                $settingGroup->$setter( $value );
            }

            $this->getService('em')->persist($settingGroup);
            $this->getService('em')->flush();

            return true;
        } catch (\Exception $e) {
            throw new EntityAlreadyExistsException($this->getTranslator()->trans(
                'settingGroup.%name%.no_creation.already_exists',
                array('%name%'=>$settingGroup->getName())
            ), 1);
        }
    }

    /**
     * @param array                          $data
     * @param RZ\Renzo\Core\Entities\SettingGroup $settingGroup
     *
     * @return boolean
     */
    private function deleteSettingGroup($data, SettingGroup $settingGroup)
    {
        $this->getService('em')->remove($settingGroup);
        $this->getService('em')->flush();

        return true;
    }

    /**
     * @param RZ\Renzo\Core\Entities\SettingGroup $settingGroup
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildAddForm(SettingGroup $settingGroup)
    {
        $defaults = array(
            'name' =>    $settingGroup->getName(),
            'inMenu' =>  $settingGroup->isInMenu()
        );
        $builder = $this->getService('formFactory')
            ->createBuilder('form', $defaults)
            ->add('name', 'text', array(
                'label' => $this->getTranslator()->trans('name'),
                'constraints' => array(
                    new NotBlank()
                )
            ))
            ->add('inMenu', 'checkbox', array(
                'label' => $this->getTranslator()->trans('settingGroup.in.menu'),
                'required' => false
            ))
            ;

        return $builder->getForm();
    }


    /**
     * @param RZ\Renzo\Core\Entities\SettingGroup $settingGroup
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildEditForm(SettingGroup $settingGroup)
    {
        $defaults = array(
            'id' =>      $settingGroup->getId(),
            'name' =>    $settingGroup->getName(),
            'inMenu' =>  $settingGroup->isInMenu()
        );

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
                    'data'=>$settingGroup->getId(),
                    'required' => true
                )
            )
            ->add(
                'inMenu',
                'checkbox',
                array(
                    'label' => $this->getTranslator()->trans('settingGroup.in.menu'),
                    'required' => false
                )
            );

        return $builder->getForm();
    }

    /**
     * @param RZ\Renzo\Core\Entities\SettingGroup $settingGroup
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildDeleteForm(SettingGroup $settingGroup)
    {
        $builder = $this->getService('formFactory')
            ->createBuilder('form')
            ->add('settingGroupId', 'hidden', array(
                'data' => $settingGroup->getId(),
                'constraints' => array(
                    new NotBlank()
                )
            ));

        return $builder->getForm();
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public static function getSettingGroups()
    {
        return $this->getService('em')
            ->getRepository('RZ\Renzo\Core\Entities\SettingGroup')
            ->findAll();
    }
}
