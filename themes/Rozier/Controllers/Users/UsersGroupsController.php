<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers\Users;

use RZ\Roadiz\CMS\Forms\GroupsType;
use RZ\Roadiz\Core\Entities\Group;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Themes\Rozier\RozierApp;

/**
 * @package Themes\Rozier\Controllers\Users
 */
class UsersGroupsController extends RozierApp
{
    /**
     * @param Request $request
     * @param int     $userId
     *
     * @return Response
     */
    public function editGroupsAction(Request $request, int $userId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_USERS');

        /** @var User|null $user */
        $user = $this->get('em')->find(User::class, $userId);

        if ($user !== null) {
            $this->assignation['user'] = $user;

            $form = $this->buildEditGroupsForm($user);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $group = $this->addUserGroup($form->getData(), $user);

                $msg = $this->getTranslator()->trans('user.%user%.group.%group%.linked', [
                    '%user%' => $user->getUserName(),
                    '%group%' => $group->getName(),
                ]);
                $this->publishConfirmMessage($request, $msg);

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl(
                    'usersEditGroupsPage',
                    ['userId' => $user->getId()]
                ));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('users/groups.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Return a deletion form for requested group depending on the user.
     *
     * @param Request $request
     * @param int     $userId
     * @param int     $groupId
     *
     * @return Response
     */
    public function removeGroupAction(Request $request, int $userId, int $groupId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_USERS');

        /** @var User|null $user */
        $user = $this->get('em')->find(User::class, $userId);
        /** @var Group|null $group */
        $group = $this->get('em')->find(Group::class, $groupId);

        if (!$this->isGranted($group)) {
            throw $this->createAccessDeniedException();
        }

        if ($user !== null) {
            $this->assignation['user'] = $user;
            $this->assignation['group'] = $group;

            $form = $this->buildRemoveGroupForm($user, $group);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $group = $this->removeUserGroup($form->getData(), $user);

                $msg = $this->getTranslator()->trans('user.%user%.group.%group%.removed', [
                    '%user%' => $user->getUserName(),
                    '%group%' => $group->getName(),
                ]);
                $this->publishConfirmMessage($request, $msg);

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl(
                    'usersEditGroupsPage',
                    ['userId' => $user->getId()]
                ));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('users/removeGroup.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * @param array $data
     * @param User  $user
     *
     * @return Group|null
     */
    private function addUserGroup($data, User $user)
    {
        if ($data['userId'] == $user->getId()) {
            if (array_key_exists('group', $data) && $data['group'][0] instanceof Group) {
                $group = $data['group'][0];
            } elseif (array_key_exists('group', $data) && is_numeric($data['group'])) {
                $group = $this->get('em')->find(Group::class, $data['group']);
            } else {
                $group = null;
            }

            if ($group !== null) {
                $user->addGroup($group);
                $this->get('em')->flush();
            }

            return $group;
        }

        return null;
    }

    /**
     * @param array $data
     * @param User  $user
     *
     * @return Group|null
     */
    private function removeUserGroup($data, User $user)
    {
        if ($data['userId'] == $user->getId()) {
            $group = $this->get('em')
                          ->find(Group::class, $data['groupId']);

            if ($group !== null) {
                $user->removeGroup($group);
                $this->get('em')->flush();
            }

            return $group;
        }

        return null;
    }

    /**
     * @param User $user
     *
     * @return FormInterface
     */
    private function buildEditGroupsForm(User $user)
    {
        $defaults = [
            'userId' => $user->getId(),
        ];
        $builder = $this->createFormBuilder($defaults)
            ->add(
                'userId',
                HiddenType::class,
                [
                    'data' => $user->getId(),
                    'constraints' => [
                        new NotNull(),
                        new NotBlank(),
                    ],
                ]
            )
            ->add(
                'group',
                GroupsType::class,
                [
                    'label' => 'Group'
                ]
            )
        ;

        return $builder->getForm();
    }

    /**
     * @param User  $user
     * @param Group $group
     *
     * @return FormInterface
     */
    private function buildRemoveGroupForm(User $user, Group $group)
    {
        $builder = $this->createFormBuilder()
                        ->add(
                            'userId',
                            HiddenType::class,
                            [
                                'data' => $user->getId(),
                                'constraints' => [
                                    new NotNull(),
                                    new NotBlank(),
                                ],
                            ]
                        )
                        ->add(
                            'groupId',
                            HiddenType::class,
                            [
                                'data' => $group->getId(),
                                'constraints' => [
                                    new NotNull(),
                                    new NotBlank(),
                                ],
                            ]
                        );

        return $builder->getForm();
    }
}
