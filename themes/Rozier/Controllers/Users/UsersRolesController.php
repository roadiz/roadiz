<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers\Users;

use RZ\Roadiz\CMS\Forms\RolesType;
use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Themes\Rozier\RozierApp;

/**
 * @package Themes\Rozier\Controllers\Users
 */
class UsersRolesController extends RozierApp
{
    /**
     * @param Request $request
     * @param int     $userId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editRolesAction(Request $request, int $userId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_USERS');

        /** @var User|null $user */
        $user = $this->get('em')->find(User::class, $userId);

        if ($user !== null) {
            $this->assignation['user'] = $user;
            $form = $this->buildEditRolesForm($user);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $role = $this->addUserRole($form->getData(), $user);

                $msg = $this->getTranslator()->trans('user.%user%.role.%role%.linked', [
                    '%user%' => $user->getUserName(),
                    '%role%' => $role->getRole(),
                ]);

                $this->publishConfirmMessage($request, $msg);

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl(
                    'usersEditRolesPage',
                    ['userId' => $user->getId()]
                ));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('users/roles.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Return a deletion form for requested role depending on the user.
     *
     * @param Request $request
     * @param int     $userId
     * @param int     $roleId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function removeRoleAction(Request $request, int $userId, int $roleId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_USERS');

        /** @var User|null $user */
        $user = $this->get('em')->find(User::class, $userId);

        /** @var Role|null $role */
        $role = $this->get('em')->find(Role::class, $roleId);

        if ($user !== null && $role !== null) {
            if (!$this->isGranted($role->getRole())) {
                throw $this->createAccessDeniedException();
            }

            $this->assignation['user'] = $user;
            $this->assignation['role'] = $role;

            $form = $this->createForm();
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $user->removeRole($role);
                $this->get('em')->flush();
                $msg = $this->getTranslator()->trans(
                    'user.%name%.role_removed',
                    ['%name%' => $role->getRole()]
                );
                $this->publishConfirmMessage($request, $msg);

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl(
                    'usersEditRolesPage',
                    ['userId' => $user->getId()]
                ));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('users/removeRole.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * @param array $data
     * @param User  $user
     *
     * @return Role|null
     */
    private function addUserRole($data, User $user)
    {
        if ($data['userId'] == $user->getId()) {
            /** @var Role|null $role */
            $role = $this->get('em')->find(Role::class, $data['roleId']);

            if (null !== $role) {
                $user->addRole($role);
                $this->get('em')->flush();
                return $role;
            }
        }

        return null;
    }

    /**
     * @param User $user
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    private function buildEditRolesForm(User $user)
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
                            'roleId',
                            RolesType::class,
                            [
                                'label' => 'choose.role',
                                'roles' => $user->getRolesEntities(),
                            ]
                        );

        return $builder->getForm();
    }
}
