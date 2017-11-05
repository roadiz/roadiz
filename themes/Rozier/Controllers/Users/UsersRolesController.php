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
 * @file UsersRolesController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers\Users;

use RZ\Roadiz\CMS\Forms\RolesType;
use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Validator\Constraints\NotBlank;
use Themes\Rozier\RozierApp;

/**
 * {@inheritdoc}
 */
class UsersRolesController extends RozierApp
{
    /**
     * Return an edition form for requested user.
     *
     * @param Request $request
     * @param int     $userId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editRolesAction(Request $request, $userId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_USERS');

        $user = $this->get('em')
                     ->find('RZ\Roadiz\Core\Entities\User', (int) $userId);

        if ($user !== null) {
            $this->assignation['user'] = $user;
            $form = $this->buildEditRolesForm($user);
            $form->handleRequest($request);

            if ($form->isValid()) {
                $role = $this->addUserRole($form->getData(), $user);

                $msg = $this->getTranslator()->trans('user.%user%.role.%role%.linked', [
                    '%user%' => $user->getUserName(),
                    '%role%' => $role->getName(),
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
    public function removeRoleAction(Request $request, $userId, $roleId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_USERS');

        $user = $this->get('em')
                     ->find('RZ\Roadiz\Core\Entities\User', (int) $userId);
        $role = $this->get('em')
                     ->find('RZ\Roadiz\Core\Entities\Role', (int) $roleId);

        if ($user !== null && $role !== null) {
            $this->assignation['user'] = $user;
            $this->assignation['role'] = $role;

            $form = $this->buildRemoveRoleForm($user, $role);
            $form->handleRequest($request);

            if ($form->isValid()) {
                $this->removeUserRole($form->getData(), $user);
                $msg = $this->getTranslator()->trans(
                    'user.%name%.role_removed',
                    ['%name%' => $role->getName()]
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
     * @return Role
     */
    private function addUserRole($data, User $user)
    {
        if ($data['userId'] == $user->getId()) {
            $role = $this->get('em')
                         ->find('RZ\Roadiz\Core\Entities\Role', $data['roleId']);

            $user->addRole($role);
            $this->get('em')->flush();

            return $role;
        }

        return null;
    }

    /**
     * @param array $data
     * @param User  $user
     *
     * @return Role
     */
    private function removeUserRole($data, User $user)
    {
        if ($data['userId'] == $user->getId()) {
            $role = $this->get('em')
                         ->find('RZ\Roadiz\Core\Entities\Role', $data['roleId']);

            if ($role !== null) {
                $user->removeRole($role);
                $this->get('em')->flush();
            }

            return $role;
        }

        return null;
    }

    /**
     * @param User $user
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildEditRolesForm(User $user)
    {
        $defaults = [
            'userId' => $user->getId(),
        ];
        $builder = $this->createFormBuilder($defaults)
                        ->add('userId',  HiddenType::class,
                            [
                                'data' => $user->getId(),
                                'constraints' => [
                                    new NotBlank(),
                                ],
                            ]
                        )
                        ->add(
                            'roleId',
                            RolesType::class,
                            [
                                'label' => 'Role',
                                'entityManager' => $this->get('em'),
                                'roles' => $user->getRolesEntities(),
                            ]
                        );

        return $builder->getForm();
    }

    /**
     * @param User $user
     * @param Role $role
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildRemoveRoleForm(User $user, Role $role)
    {
        $builder = $this->createFormBuilder()
                        ->add('userId',  HiddenType::class,
                            [
                                'data' => $user->getId(),
                                'constraints' => [
                                    new NotBlank(),
                                ],
                            ]
                        )
                        ->add('roleId',  HiddenType::class,
                            [
                                'data' => $role->getId(),
                                'constraints' => [
                                    new NotBlank(),
                                ],
                            ]
                        );

        return $builder->getForm();
    }
}
