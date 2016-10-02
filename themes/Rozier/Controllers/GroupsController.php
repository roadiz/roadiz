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
 * @file GroupsController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Entities\Group;
use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;
use Themes\Rozier\RozierApp;

/**
 * {@inheritdoc}
 */
class GroupsController extends RozierApp
{
    /**
     * List groups action.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_GROUPS');

        /*
         * Manage get request to filter list
         */
        $listManager = $this->createEntityListManager(
            'RZ\Roadiz\Core\Entities\Group'
        );
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['groups'] = $listManager->getEntities();

        return $this->render('groups/list.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_GROUPS');

        $form = $this->buildAddForm();
        $form->handleRequest($request);

        if ($form->isValid()) {
            try {
                $group = $this->addGroup($form->getData());
                $msg = $this->getTranslator()->trans(
                    'group.%name%.created',
                    ['%name%' => $group->getName()]
                );
                $this->publishConfirmMessage($request, $msg);
            } catch (EntityAlreadyExistsException $e) {
                $this->publishErrorMessage($request, $e->getMessage());
            } catch (\RuntimeException $e) {
                $this->publishErrorMessage($request, $e->getMessage());
            }

            return $this->redirect($this->generateUrl('groupsHomePage'));
        }

        $this->assignation['form'] = $form->createView();

        return $this->render('groups/add.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     * @param int     $groupId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $groupId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_GROUPS');

        $group = $this->get('em')
                      ->find('RZ\Roadiz\Core\Entities\Group', (int) $groupId);

        if ($group !== null) {
            $form = $this->buildDeleteForm($group);
            $form->handleRequest($request);

            if ($form->isValid() &&
                $form->getData()['groupId'] == $group->getId()) {
                try {
                    $this->deleteGroup($form->getData(), $group);
                    $msg = $this->getTranslator()->trans(
                        'group.%name%.deleted',
                        ['%name%' => $group->getName()]
                    );
                    $this->publishConfirmMessage($request, $msg);
                } catch (\RuntimeException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                }

                return $this->redirect($this->generateUrl('groupsHomePage'));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('groups/delete.html.twig', $this->assignation);
        } else {
            return $this->throw404();
        }
    }

    /**
     * @param Request $request
     * @param int     $groupId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $groupId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_GROUPS');

        $group = $this->get('em')
                      ->find('RZ\Roadiz\Core\Entities\Group', (int) $groupId);

        if ($group !== null) {
            $this->assignation['group'] = $group;

            $form = $this->buildEditForm($group);
            $form->handleRequest($request);

            if ($form->isValid() &&
                $form->getData()['groupId'] == $group->getId()) {
                try {
                    $this->editGroup($form->getData(), $group);
                    $msg = $this->getTranslator()->trans(
                        'group.%name%.updated',
                        ['%name%' => $group->getName()]
                    );
                    $this->publishConfirmMessage($request, $msg);
                } catch (EntityAlreadyExistsException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                } catch (\RuntimeException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                }

                return $this->redirect($this->generateUrl('groupsHomePage'));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('groups/edit.html.twig', $this->assignation);
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return an edition form for requested group.
     *
     * @param Request $request
     * @param int     $groupId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editRolesAction(Request $request, $groupId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_GROUPS');

        $group = $this->get('em')
                      ->find('RZ\Roadiz\Core\Entities\Group', (int) $groupId);

        if ($group !== null) {
            $this->assignation['group'] = $group;
            $form = $this->buildEditRolesForm($group);
            $form->handleRequest($request);

            if ($form->isValid()) {
                $role = $this->addRole($form->getData(), $group);

                $msg = $this->getTranslator()->trans('role.%role%.linked_group.%group%', [
                    '%group%' => $group->getName(),
                    '%role%' => $role->getName(),
                ]);
                $this->publishConfirmMessage($request, $msg);

                return $this->redirect($this->generateUrl(
                    'groupsEditRolesPage',
                    ['groupId' => $group->getId()]
                ));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('groups/roles.html.twig', $this->assignation);
        } else {
            return $this->throw404();
        }
    }

    /**
     * @param Request $request
     * @param int     $groupId
     * @param int     $roleId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function removeRolesAction(Request $request, $groupId, $roleId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_GROUPS');

        $group = $this->get('em')
                      ->find('RZ\Roadiz\Core\Entities\Group', (int) $groupId);
        $role = $this->get('em')
                     ->find('RZ\Roadiz\Core\Entities\Role', (int) $roleId);

        if ($group !== null &&
            $role !== null) {
            $this->assignation['group'] = $group;
            $this->assignation['role'] = $role;

            $form = $this->buildRemoveRoleForm($group, $role);
            $form->handleRequest($request);

            if ($form->isValid()) {
                $this->removeRole($form->getData(), $group, $role);
                $msg = $this->getTranslator()->trans('role.%role%.removed_from_group.%group%', [
                    '%role%' => $role->getName(),
                    '%group%' => $group->getName(),
                ]);
                $this->publishConfirmMessage($request, $msg);

                return $this->redirect($this->generateUrl(
                    'groupsEditRolesPage',
                    ['groupId' => $group->getId()]
                ));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('groups/removeRole.html.twig', $this->assignation);
        } else {
            return $this->throw404();
        }
    }

    /**
     * @param Request $request
     * @param int     $groupId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editUsersAction(Request $request, $groupId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_GROUPS');

        $group = $this->get('em')
                      ->find('RZ\Roadiz\Core\Entities\Group', (int) $groupId);

        if ($group !== null) {
            $this->assignation['group'] = $group;
            $form = $this->buildEditUsersForm($group);
            $form->handleRequest($request);

            if ($form->isValid()) {
                $user = $this->addUser($form->getData(), $group);

                $msg = $this->getTranslator()->trans('user.%user%.linked.group.%group%', [
                    '%group%' => $group->getName(),
                    '%user%' => $user->getUserName(),
                ]);
                $this->publishConfirmMessage($request, $msg);

                return $this->redirect($this->generateUrl(
                    'groupsEditUsersPage',
                    ['groupId' => $group->getId()]
                ));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('groups/users.html.twig', $this->assignation);
        } else {
            return $this->throw404();
        }
    }

    /**
     * @param Request $request
     * @param int     $groupId
     * @param int     $userId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function removeUsersAction(Request $request, $groupId, $userId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_GROUPS');

        $group = $this->get('em')
                      ->find('RZ\Roadiz\Core\Entities\Group', (int) $groupId);
        $user = $this->get('em')
                     ->find('RZ\Roadiz\Core\Entities\User', (int) $userId);

        if ($group !== null &&
            $user !== null) {
            $this->assignation['group'] = $group;
            $this->assignation['user'] = $user;

            $form = $this->buildRemoveUserForm($group, $user);
            $form->handleRequest($request);

            if ($form->isValid()) {
                $this->removeUser($form->getData(), $group, $user);
                $msg = $this->getTranslator()->trans('user.%user%.removed_from_group.%group%', [
                    '%user%' => $user->getUserName(),
                    '%group%' => $group->getName(),
                ]);
                $this->publishConfirmMessage($request, $msg);

                return $this->redirect($this->generateUrl(
                    'groupsEditUsersPage',
                    ['groupId' => $group->getId(),
                        'userId' => $user->getId()]
                ));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('groups/removeUser.html.twig', $this->assignation);
        } else {
            return $this->throw404();
        }
    }

    /**
     * Build add group form with name constraint.
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function buildAddForm()
    {
        $builder = $this->createFormBuilder()
                        ->add('name', 'text', [
                            'label' => 'group.name',
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ]);

        return $builder->getForm();
    }

    /**
     * Build edit group form with name constraint.
     *
     * @param Group $group
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function buildEditForm(Group $group)
    {
        $defaults = [
            'name' => $group->getName(),
        ];
        $builder = $this->createFormBuilder($defaults)
                        ->add('groupId', 'hidden', [
                            'data' => $group->getId(),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ])
                        ->add('name', 'text', [
                            'label' => 'group.name',
                            'data' => $group->getName(),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ]);

        return $builder->getForm();
    }

    /**
     * Build delete group form with name constraint.
     *
     * @param Group $group
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function buildDeleteForm(Group $group)
    {
        $builder = $this->createFormBuilder()
                        ->add('groupId', 'hidden', [
                            'data' => $group->getId(),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ]);

        return $builder->getForm();
    }

    /**
     * @param Group $group
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildEditRolesForm(Group $group)
    {
        $defaults = [
            'groupId' => $group->getId(),
        ];
        $builder = $this->createFormBuilder($defaults)
                        ->add('groupId', 'hidden', [
                            'data' => $group->getId(),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ])
                        ->add(
                            'roleId',
                            new \RZ\Roadiz\CMS\Forms\RolesType($group->getRolesEntities()),
                            ['label' => 'choose.role']
                        );

        return $builder->getForm();
    }

    /**
     * @param Group $group
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildEditUsersForm(Group $group)
    {
        $defaults = [
            'groupId' => $group->getId(),
        ];
        $builder = $this->createFormBuilder($defaults)
                        ->add('groupId', 'hidden', [
                            'data' => $group->getId(),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ])
                        ->add(
                            'userId',
                            new \RZ\Roadiz\CMS\Forms\UsersType($group->getUsers()),
                            [
                                'label' => 'choose.user',
                                'constraints' => [
                                    new NotBlank(),
                                ],
                            ]
                        );

        return $builder->getForm();
    }

    /**
     * @param Group $group
     * @param Role  $role
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildRemoveRoleForm(Group $group, Role $role)
    {
        $builder = $this->createFormBuilder()
                        ->add('groupId', 'hidden', [
                            'data' => $group->getId(),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ])
                        ->add('roleId', 'hidden', [
                            'data' => $role->getId(),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ]);

        return $builder->getForm();
    }

    /**
     * @param Group $group
     * @param User  $user
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildRemoveUserForm(Group $group, User $user)
    {
        $builder = $this->createFormBuilder()
                        ->add('groupId', 'hidden', [
                            'data' => $group->getId(),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ])
                        ->add('userId', 'hidden', [
                            'data' => $user->getId(),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ]);

        return $builder->getForm();
    }

    /**
     * @param array $data
     *
     * @return Group
     * @throws EntityAlreadyExistsException
     */
    protected function addGroup(array $data)
    {
        if (isset($data['name'])) {
            $existing = $this->get('em')
                             ->getRepository('RZ\Roadiz\Core\Entities\Group')
                             ->findOneBy(['name' => $data['name']]);

            if ($existing !== null) {
                throw new EntityAlreadyExistsException($this->getTranslator()->trans("group.name.already.exists"), 1);
            }

            $group = new Group();
            $group->setName($data['name']);
            $this->get('em')->persist($group);
            $this->get('em')->flush();

            return $group;
        } else {
            throw new \RuntimeException("Group name is not defined", 1);
        }
    }

    /**
     * @param array $data
     * @param Group $group
     *
     * @return Group
     * @throws EntityAlreadyExistsException
     */
    protected function editGroup(array $data, Group $group)
    {
        if (isset($data['name'])) {
            $existing = $this->get('em')
                             ->getRepository('RZ\Roadiz\Core\Entities\Group')
                             ->findOneBy(['name' => $data['name']]);
            if ($existing !== null &&
                $existing->getId() != $group->getId()) {
                throw new EntityAlreadyExistsException($this->getTranslator()->trans("group.name.already.exists"), 1);
            }

            $group->setName($data['name']);
            $this->get('em')->flush();

            return $group;
        } else {
            throw new \RuntimeException("Group name is not defined", 1);
        }
    }

    /**
     * @param array $data
     * @param Group $group
     */
    protected function deleteGroup(array $data, Group $group)
    {
        $this->get('em')->remove($group);
        $this->get('em')->flush();
    }

    /**
     * @param array $data
     * @param Group $group
     *
     * @return Role
     */
    private function addRole($data, Group $group)
    {
        if ($data['groupId'] == $group->getId()) {
            $role = $this->get('em')
                         ->find('RZ\Roadiz\Core\Entities\Role', (int) $data['roleId']);
            if ($role !== null) {
                $group->addRole($role);
                $this->get('em')->flush();

                return $role;
            }
        }
    }

    /**
     * @param array $data
     * @param Group $group
     * @param Role  $role
     *
     * @return Role
     */
    private function removeRole($data, Group $group, Role $role)
    {
        if ($data['groupId'] == $group->getId() &&
            $data['roleId'] == $role->getId()) {
            if ($role !== null) {
                $group->removeRole($role);
                $this->get('em')->flush();
            }

            return $role;
        }
    }

    /**
     * @param array $data
     * @param Group $group
     *
     * @return User
     */
    private function addUser($data, Group $group)
    {
        if ($data['groupId'] == $group->getId()) {
            $user = $this->get('em')
                         ->find('RZ\Roadiz\Core\Entities\User', (int) $data['userId']);

            if ($user !== null) {
                $user->addGroup($group);
                $this->get('em')->flush();

                return $user;
            }
        }
    }

    /**
     * @param array $data
     * @param Group $group
     * @param User  $user
     *
     * @return User
     */
    private function removeUser($data, Group $group, User $user)
    {
        if ($data['groupId'] == $group->getId() &&
            $data['userId'] == $user->getId()) {
            if ($user !== null) {
                $user->removeGroup($group);
                $this->get('em')->flush();
            }

            return $user;
        }
    }
}
