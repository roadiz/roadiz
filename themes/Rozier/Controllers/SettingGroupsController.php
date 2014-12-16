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
 * @file SettingGroupsController.php
 * @author Ambroise Maupate
 */

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\SettingGroup;
use RZ\Roadiz\Core\ListManagers\EntityListManager;
use Themes\Rozier\RozierApp;

use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Validator\Constraints\NotBlank;

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
            'RZ\Roadiz\Core\Entities\SettingGroup',
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
            ->find('RZ\Roadiz\Core\Entities\SettingGroup', (int) $settingGroupId);

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
            ->find('RZ\Roadiz\Core\Entities\SettingGroup', (int) $settingGroupId);

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
     * @param RZ\Roadiz\Core\Entities\SettingGroup $settingGroup
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
                ->getRepository('RZ\Roadiz\Core\Entities\SettingGroup')
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
                                 ->find('RZ\Roadiz\Core\Entities\SettingGroupGroup', (int) $value);
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
     * @param RZ\Roadiz\Core\Entities\SettingGroup $settingGroup
     *
     * @return boolean
     */
    private function addSettingGroup($data, SettingGroup $settingGroup)
    {
        if ($this->getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\SettingGroup')
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
     * @param RZ\Roadiz\Core\Entities\SettingGroup $settingGroup
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
     * @param RZ\Roadiz\Core\Entities\SettingGroup $settingGroup
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
     * @param RZ\Roadiz\Core\Entities\SettingGroup $settingGroup
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
     * @param RZ\Roadiz\Core\Entities\SettingGroup $settingGroup
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
        return Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\SettingGroup')
            ->findAll();
    }
}
