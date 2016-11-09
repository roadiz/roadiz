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

use RZ\Roadiz\Core\Entities\SettingGroup;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;
use Themes\Rozier\RozierApp;

/**
 * SettingGroups controller
 */
class SettingGroupsController extends RozierApp
{
    /**
     * List every settingGroups.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_SETTINGS');
        /*
         * Manage get request to filter list
         */
        $listManager = $this->createEntityListManager(
            'RZ\Roadiz\Core\Entities\SettingGroup',
            [],
            ['name' => 'ASC']
        );
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['settingGroups'] = $listManager->getEntities();

        return $this->render('settingGroups/list.html.twig', $this->assignation);
    }

    /**
     * Return an edition form for requested settingGroup.
     *
     * @param Request $request
     * @param int     $settingGroupId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $settingGroupId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_SETTINGS');

        $settingGroup = $this->get('em')
                             ->find('RZ\Roadiz\Core\Entities\SettingGroup', (int) $settingGroupId);

        if ($settingGroup !== null) {
            $this->assignation['settingGroup'] = $settingGroup;

            $form = $this->buildEditForm($settingGroup);
            $form->handleRequest($request);

            if ($form->isValid()) {
                try {
                    $this->editSettingGroup($form->getData(), $settingGroup);
                    $msg = $this->getTranslator()->trans(
                        'settingGroup.%name%.updated',
                        ['%name%' => $settingGroup->getName()]
                    );
                    $this->publishConfirmMessage($request, $msg);
                } catch (EntityAlreadyExistsException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                }
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl(
                    'settingGroupsEditPage',
                    ['settingGroupId' => $settingGroup->getId()]
                ));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('settingGroups/edit.html.twig', $this->assignation);
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return an creation form for requested settingGroup.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_SETTINGS');

        $settingGroup = new SettingGroup();

        if (null !== $settingGroup) {
            $this->assignation['settingGroup'] = $settingGroup;

            $form = $this->buildAddForm($settingGroup);

            $form->handleRequest($request);

            if ($form->isValid()) {
                try {
                    $this->addSettingGroup($form->getData(), $settingGroup);
                    $msg = $this->getTranslator()->trans(
                        'settingGroup.%name%.created',
                        ['%name%' => $settingGroup->getName()]
                    );
                    $this->publishConfirmMessage($request, $msg);
                } catch (EntityAlreadyExistsException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                }

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl('settingGroupsHomePage'));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('settingGroups/add.html.twig', $this->assignation);
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return an deletion form for requested settingGroup.
     *
     * @param Request $request
     * @param int     $settingGroupId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $settingGroupId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_SETTINGS');

        $settingGroup = $this->get('em')
                             ->find('RZ\Roadiz\Core\Entities\SettingGroup', (int) $settingGroupId);

        if (null !== $settingGroup) {
            $this->assignation['settingGroup'] = $settingGroup;

            $form = $this->buildDeleteForm($settingGroup);

            $form->handleRequest($request);

            if ($form->isValid() &&
                $form->getData()['settingGroupId'] == $settingGroup->getId()) {
                $this->deleteSettingGroup($form->getData(), $settingGroup);

                $msg = $this->getTranslator()->trans(
                    'settingGroup.%name%.deleted',
                    ['%name%' => $settingGroup->getName()]
                );
                $this->publishConfirmMessage($request, $msg);

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl('settingGroupsHomePage'));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('settingGroups/delete.html.twig', $this->assignation);
        } else {
            return $this->throw404();
        }
    }

    /**
     * @param array        $data
     * @param SettingGroup $settingGroup
     *
     * @return bool
     * @throws EntityAlreadyExistsException
     */
    private function editSettingGroup($data, SettingGroup $settingGroup)
    {
        if ($data['id'] == $settingGroup->getId()) {
            unset($data['id']);

            if (isset($data['name']) &&
                $data['name'] != $settingGroup->getName() &&
                $this->get('em')
                ->getRepository('RZ\Roadiz\Core\Entities\SettingGroup')
                ->exists($data['name'])) {
                throw new EntityAlreadyExistsException($this->getTranslator()->trans(
                    'settingGroup.%name%.no_update.already_exists',
                    ['%name%' => $settingGroup->getName()]
                ), 1);
            }
            try {
                foreach ($data as $key => $value) {
                    $setter = 'set' . ucwords($key);
                    $settingGroup->$setter($value);
                }

                $this->get('em')->flush();

                return true;
            } catch (\Exception $e) {
                throw new EntityAlreadyExistsException($this->getTranslator()->trans(
                    'settingGroup.%name%.no_update.already_exists',
                    ['%name%' => $settingGroup->getName()]
                ), 1);
            }
        }
    }

    /**
     * @param array        $data
     * @param SettingGroup $settingGroup
     *
     * @return bool
     * @throws EntityAlreadyExistsException
     */
    private function addSettingGroup($data, SettingGroup $settingGroup)
    {
        if ($this->get('em')
            ->getRepository('RZ\Roadiz\Core\Entities\SettingGroup')
            ->exists($data['name'])) {
            throw new EntityAlreadyExistsException($this->getTranslator()->trans(
                'settingGroup.%name%.no_creation.already_exists',
                ['%name%' => $settingGroup->getName()]
            ), 1);
        }

        try {
            foreach ($data as $key => $value) {
                $setter = 'set' . ucwords($key);
                $settingGroup->$setter($value);
            }

            $this->get('em')->persist($settingGroup);
            $this->get('em')->flush();

            return true;
        } catch (\Exception $e) {
            throw new EntityAlreadyExistsException($this->getTranslator()->trans(
                'settingGroup.%name%.no_creation.already_exists',
                ['%name%' => $settingGroup->getName()]
            ), 1);
        }
    }

    /**
     * @param array        $data
     * @param SettingGroup $settingGroup
     *
     * @return boolean
     */
    private function deleteSettingGroup($data, SettingGroup $settingGroup)
    {
        $this->get('em')->remove($settingGroup);
        $this->get('em')->flush();

        return true;
    }

    /**
     * @param SettingGroup $settingGroup
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildAddForm(SettingGroup $settingGroup)
    {
        $defaults = [
            'name' => $settingGroup->getName(),
            'inMenu' => $settingGroup->isInMenu(),
        ];
        $builder = $this->createFormBuilder($defaults)
                        ->add('name', 'text', [
                            'label' => 'name',
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ])
                        ->add('inMenu', 'checkbox', [
                            'label' => 'settingGroup.in.menu',
                            'required' => false,
                        ])
        ;

        return $builder->getForm();
    }

    /**
     * @param SettingGroup $settingGroup
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildEditForm(SettingGroup $settingGroup)
    {
        $defaults = [
            'id' => $settingGroup->getId(),
            'name' => $settingGroup->getName(),
            'inMenu' => $settingGroup->isInMenu(),
        ];

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
                                'data' => $settingGroup->getId(),
                                'required' => true,
                            ]
                        )
                        ->add(
                            'inMenu',
                            'checkbox',
                            [
                                'label' => 'settingGroup.in.menu',
                                'required' => false,
                            ]
                        );

        return $builder->getForm();
    }

    /**
     * @param SettingGroup $settingGroup
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildDeleteForm(SettingGroup $settingGroup)
    {
        $builder = $this->createFormBuilder()
                        ->add('settingGroupId', 'hidden', [
                            'data' => $settingGroup->getId(),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ]);

        return $builder->getForm();
    }
}
