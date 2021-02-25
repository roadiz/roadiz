<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\CMS\Forms\RolesType;
use RZ\Roadiz\CMS\Forms\UsersType;
use RZ\Roadiz\Core\AbstractEntities\PersistableInterface;
use RZ\Roadiz\Core\Entities\Group;
use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Themes\Rozier\Forms\GroupType;

/**
 * @package Themes\Rozier\Controllers
 */
class GroupsController extends AbstractAdminController
{
    /**
     * @inheritDoc
     */
    protected function supports(PersistableInterface $item): bool
    {
        return $item instanceof Group;
    }

    /**
     * @inheritDoc
     */
    protected function getNamespace(): string
    {
        return 'group';
    }

    /**
     * @inheritDoc
     */
    protected function createEmptyItem(Request $request): PersistableInterface
    {
        return new Group();
    }

    /**
     * @inheritDoc
     */
    protected function getTemplateFolder(): string
    {
        return 'groups';
    }

    /**
     * @inheritDoc
     */
    protected function getRequiredRole(): string
    {
        return 'ROLE_ACCESS_GROUPS';
    }

    /**
     * @inheritDoc
     */
    protected function getEntityClass(): string
    {
        return Group::class;
    }

    /**
     * @inheritDoc
     */
    protected function getFormType(): string
    {
        return GroupType::class;
    }

    /**
     * @inheritDoc
     */
    protected function getDefaultRouteName(): string
    {
        return 'groupsHomePage';
    }

    /**
     * @inheritDoc
     */
    protected function getEditRouteName(): string
    {
        return 'groupsEditPage';
    }

    /**
     * @inheritDoc
     */
    protected function getEntityName(PersistableInterface $item): string
    {
        if ($item instanceof Group) {
            return $item->getName();
        }
        throw new \InvalidArgumentException('Item should be instance of '.$this->getEntityClass());
    }

    /**
     * @inheritDoc
     */
    protected function denyAccessUnlessItemGranted(PersistableInterface $item): void
    {
        $this->denyAccessUnlessGranted($item);
    }

    /**
     * Return an edition form for requested group.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editRolesAction(Request $request, int $id)
    {
        $this->denyAccessUnlessGranted($this->getRequiredRole());

        /** @var Group|null $item */
        $item = $this->get('em')->find($this->getEntityClass(), $id);

        if (null === $item || !($item instanceof Group)) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessItemGranted($item);

        $this->assignation['item'] = $item;
        $form = $this->buildEditRolesForm($item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $role = $this->addRole($form->getData(), $item);

            $msg = $this->getTranslator()->trans('role.%role%.linked_group.%group%', [
                '%group%' => $item->getName(),
                '%role%' => $role->getRole(),
            ]);
            $this->publishConfirmMessage($request, $msg);

            return $this->redirect($this->generateUrl(
                'groupsEditRolesPage',
                ['id' => $item->getId()]
            ));
        }

        $this->assignation['form'] = $form->createView();

        return $this->render('groups/roles.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     * @param int     $id
     * @param int     $roleId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function removeRolesAction(Request $request, int $id, int $roleId)
    {
        $this->denyAccessUnlessGranted($this->getRequiredRole());

        /** @var Group|null $item */
        $item = $this->get('em')->find($this->getEntityClass(), $id);

        /** @var Role|null $role */
        $role = $this->get('em')->find(Role::class, $roleId);

        if (null === $item || !($item instanceof Group)) {
            throw $this->createNotFoundException();
        }

        if (null === $role || !($role instanceof Role)) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessItemGranted($item);

        $this->assignation['item'] = $item;
        $this->assignation['role'] = $role;

        $form = $this->buildRemoveRoleForm($item, $role);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->removeRole($form->getData(), $item, $role);
            $msg = $this->getTranslator()->trans('role.%role%.removed_from_group.%group%', [
                '%role%' => $role->getRole(),
                '%group%' => $item->getName(),
            ]);
            $this->publishConfirmMessage($request, $msg);

            return $this->redirect($this->generateUrl(
                'groupsEditRolesPage',
                ['id' => $item->getId()]
            ));
        }

        $this->assignation['form'] = $form->createView();

        return $this->render('groups/removeRole.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     * @param int     $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editUsersAction(Request $request, int $id)
    {
        $this->denyAccessUnlessGranted($this->getRequiredRole());

        /** @var Group|null $item */
        $item = $this->get('em')->find($this->getEntityClass(), $id);

        if (null === $item || !($item instanceof Group)) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessItemGranted($item);

        $this->assignation['item'] = $item;
        $form = $this->buildEditUsersForm($item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->addUser($form->getData(), $item);

            $msg = $this->getTranslator()->trans('user.%user%.linked.group.%group%', [
                '%group%' => $item->getName(),
                '%user%' => $user->getUserName(),
            ]);
            $this->publishConfirmMessage($request, $msg);

            return $this->redirect($this->generateUrl(
                'groupsEditUsersPage',
                ['id' => $item->getId()]
            ));
        }

        $this->assignation['form'] = $form->createView();

        return $this->render('groups/users.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     * @param int     $id
     * @param int     $userId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function removeUsersAction(Request $request, int $id, int $userId)
    {
        $this->denyAccessUnlessGranted($this->getRequiredRole());

        /** @var Group|null $item */
        $item = $this->get('em')->find($this->getEntityClass(), $id);
        /** @var User|null $user */
        $user = $this->get('em')->find(User::class, $userId);

        if (null === $item || !($item instanceof Group)) {
            throw $this->createNotFoundException();
        }

        if (null === $user) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessItemGranted($item);

        $this->assignation['item'] = $item;
        $this->assignation['user'] = $user;

        $form = $this->buildRemoveUserForm($item, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->removeUser($form->getData(), $item, $user);
            $msg = $this->getTranslator()->trans('user.%user%.removed_from_group.%group%', [
                '%user%' => $user->getUserName(),
                '%group%' => $item->getName(),
            ]);
            $this->publishConfirmMessage($request, $msg);

            return $this->redirect($this->generateUrl(
                'groupsEditUsersPage',
                [
                    'id' => $item->getId()
                ]
            ));
        }

        $this->assignation['form'] = $form->createView();

        return $this->render('groups/removeUser.html.twig', $this->assignation);
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
                                'roles' => $group->getRolesEntities(),
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
