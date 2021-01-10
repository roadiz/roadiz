<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\CMS\Forms\RolesType;
use RZ\Roadiz\CMS\Forms\UsersType;
use RZ\Roadiz\Core\Entities\Group;
use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Themes\Rozier\RozierApp;

/**
 * @package Themes\Rozier\Controllers
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
        $this->denyAccessUnlessGranted('ROLE_ACCESS_GROUPS');

        /*
         * Manage get request to filter list
         */
        $listManager = $this->createEntityListManager(
            Group::class
        );
        $listManager->setDisplayingNotPublishedNodes(true);
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
        $this->denyAccessUnlessGranted('ROLE_ACCESS_GROUPS');

        $form = $this->buildAddForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
        $this->denyAccessUnlessGranted('ROLE_ACCESS_GROUPS');

        /** @var Group|null $group */
        $group = $this->get('em')
                      ->find(Group::class, (int) $groupId);

        if ($group !== null) {
            if (!$this->isGranted($group)) {
                throw $this->createAccessDeniedException();
            }
            $form = $this->buildDeleteForm($group);
            $form->handleRequest($request);

            if ($form->isSubmitted() &&
                $form->isValid() &&
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

            $this->assignation['group'] = $group;
            $this->assignation['form'] = $form->createView();

            return $this->render('groups/delete.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * @param Request $request
     * @param int     $groupId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $groupId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_GROUPS');

        /** @var Group|null $group */
        $group = $this->get('em')
                      ->find(Group::class, (int) $groupId);

        if ($group !== null) {
            if (!$this->isGranted($group)) {
                throw $this->createAccessDeniedException();
            }
            $this->assignation['group'] = $group;

            $form = $this->buildEditForm($group);
            $form->handleRequest($request);

            if ($form->isSubmitted() &&
                $form->isValid() &&
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
        }

        throw new ResourceNotFoundException();
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
        $this->denyAccessUnlessGranted('ROLE_ACCESS_GROUPS');

        /** @var Group|null $group */
        $group = $this->get('em')
                      ->find(Group::class, (int) $groupId);

        if ($group !== null) {
            if (!$this->isGranted($group)) {
                throw $this->createAccessDeniedException();
            }
            $this->assignation['group'] = $group;
            $form = $this->buildEditRolesForm($group);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
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
        }

        throw new ResourceNotFoundException();
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
        $this->denyAccessUnlessGranted('ROLE_ACCESS_GROUPS');

        /** @var Group|null $group */
        $group = $this->get('em')
                      ->find(Group::class, (int) $groupId);
        /** @var Role|null $role */
        $role = $this->get('em')
                     ->find(Role::class, (int) $roleId);

        if ($group !== null &&
            $role !== null) {
            if (!$this->isGranted($group)) {
                throw $this->createAccessDeniedException();
            }
            $this->assignation['group'] = $group;
            $this->assignation['role'] = $role;

            $form = $this->buildRemoveRoleForm($group, $role);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $this->removeRole($form->getData(), $group, $role);
                $msg = $this->getTranslator()->trans('role.%role%.removed_from_group.%group%', [
                    '%role%' => $role->getRole(),
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
        }

        throw new ResourceNotFoundException();
    }

    /**
     * @param Request $request
     * @param int     $groupId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editUsersAction(Request $request, $groupId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_GROUPS');

        /** @var Group $group */
        $group = $this->get('em')
                      ->find(Group::class, (int) $groupId);

        if ($group !== null) {
            if (!$this->isGranted($group)) {
                throw $this->createAccessDeniedException();
            }
            $this->assignation['group'] = $group;
            $form = $this->buildEditUsersForm($group);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
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
        }

        throw new ResourceNotFoundException();
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
        $this->denyAccessUnlessGranted('ROLE_ACCESS_GROUPS');

        /** @var Group|null $group */
        $group = $this->get('em')
                      ->find(Group::class, (int) $groupId);
        /** @var User|null $user */
        $user = $this->get('em')
                     ->find(User::class, (int) $userId);

        if ($group !== null &&
            $user !== null) {
            if (!$this->isGranted($group)) {
                throw $this->createAccessDeniedException();
            }
            $this->assignation['group'] = $group;
            $this->assignation['user'] = $user;

            $form = $this->buildRemoveUserForm($group, $user);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
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
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Build add group form with name constraint.
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function buildAddForm()
    {
        $builder = $this->createFormBuilder()
                        ->add('name', TextType::class, [
                            'label' => 'group.name',
                            'constraints' => [
                                new NotNull(),
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
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function buildEditForm(Group $group)
    {
        $defaults = [
            'name' => $group->getName(),
        ];
        $builder = $this->createFormBuilder($defaults)
                        ->add('groupId', HiddenType::class, [
                            'data' => $group->getId(),
                            'constraints' => [
                                new NotNull(),
                                new NotBlank(),
                            ],
                        ])
                        ->add('name', TextType::class, [
                            'label' => 'group.name',
                            'data' => $group->getName(),
                            'constraints' => [
                                new NotNull(),
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
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function buildDeleteForm(Group $group)
    {
        $builder = $this->createFormBuilder()
                        ->add('groupId', HiddenType::class, [
                            'data' => $group->getId(),
                            'constraints' => [
                                new NotNull(),
                                new NotBlank(),
                            ],
                        ]);

        return $builder->getForm();
    }

    /**
     * @param Group $group
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    private function buildEditRolesForm(Group $group)
    {
        $defaults = [
            'groupId' => $group->getId(),
        ];
        $builder = $this->createFormBuilder($defaults)
                        ->add('groupId', HiddenType::class, [
                            'data' => $group->getId(),
                            'constraints' => [
                                new NotNull(),
                                new NotBlank(),
                            ],
                        ])
                        ->add(
                            'roleId',
                            RolesType::class,
                            [
                                'label' => 'choose.role',
                                'entityManager' => $this->get('em'),
                                'roles' => $group->getRolesEntities(),
                                'authorizationChecker' => $this->get('securityAuthorizationChecker'),
                            ]
                        );

        return $builder->getForm();
    }

    /**
     * @param Group $group
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    private function buildEditUsersForm(Group $group)
    {
        $defaults = [
            'groupId' => $group->getId(),
        ];
        $builder = $this->createFormBuilder($defaults)
                        ->add('groupId', HiddenType::class, [
                            'data' => $group->getId(),
                            'constraints' => [
                                new NotNull(),
                                new NotBlank(),
                            ],
                        ])
                        ->add(
                            'userId',
                            UsersType::class,
                            [
                                'label' => 'choose.user',
                                'constraints' => [
                                    new NotNull(),
                                    new NotBlank(),
                                ],
                                'entityManager' => $this->get('em'),
                                'users' => $group->getUsers(),
                            ]
                        );

        return $builder->getForm();
    }

    /**
     * @param Group $group
     * @param Role  $role
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    private function buildRemoveRoleForm(Group $group, Role $role)
    {
        $builder = $this->createFormBuilder()
                        ->add('groupId', HiddenType::class, [
                            'data' => $group->getId(),
                            'constraints' => [
                                new NotNull(),
                                new NotBlank(),
                            ],
                        ])
                        ->add('roleId', HiddenType::class, [
                            'data' => $role->getId(),
                            'constraints' => [
                                new NotNull(),
                                new NotBlank(),
                            ],
                        ]);

        return $builder->getForm();
    }

    /**
     * @param Group $group
     * @param User  $user
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    private function buildRemoveUserForm(Group $group, User $user)
    {
        $builder = $this->createFormBuilder()
                        ->add('groupId', HiddenType::class, [
                            'data' => $group->getId(),
                            'constraints' => [
                                new NotNull(),
                                new NotBlank(),
                            ],
                        ])
                        ->add('userId', HiddenType::class, [
                            'data' => $user->getId(),
                            'constraints' => [
                                new NotNull(),
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
                             ->getRepository(Group::class)
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
            /** @var Group|null $existing */
            $existing = $this->get('em')
                             ->getRepository(Group::class)
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
     * @return Role|null
     */
    private function addRole($data, Group $group)
    {
        if ($data['groupId'] == $group->getId()) {
            $role = $this->get('em')->find(Role::class, (int) $data['roleId']);
            if ($role !== null) {
                $group->addRole($role);
                $this->get('em')->flush();

                return $role;
            }
        }
        return null;
    }

    /**
     * @param array $data
     * @param Group $group
     * @param Role  $role
     *
     * @return Role|null
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
        return null;
    }

    /**
     * @param array $data
     * @param Group $group
     *
     * @return User|null
     */
    private function addUser($data, Group $group)
    {
        if ($data['groupId'] == $group->getId()) {
            /** @var User|null $user */
            $user = $this->get('em')
                         ->find(User::class, (int) $data['userId']);

            if ($user !== null) {
                $user->addGroup($group);
                $this->get('em')->flush();

                return $user;
            }
        }
        return null;
    }

    /**
     * @param array $data
     * @param Group $group
     * @param User  $user
     *
     * @return User|null
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
        return null;
    }
}
