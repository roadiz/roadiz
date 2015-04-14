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

use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\HttpFoundation\Request;
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
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $userId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function editRolesAction(Request $request, $userId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_USERS');

        $user = $this->getService('em')
                     ->find('RZ\Roadiz\Core\Entities\User', (int) $userId);

        if ($user !== null) {
            $this->assignation['user'] = $user;
            $form = $this->buildEditRolesForm($user);
            $form->handleRequest();

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
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return a deletion form for requested role depending on the user.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $userId
     * @param int                                      $roleId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function removeRoleAction(Request $request, $userId, $roleId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_USERS');

        $user = $this->getService('em')
                     ->find('RZ\Roadiz\Core\Entities\User', (int) $userId);
        $role = $this->getService('em')
                     ->find('RZ\Roadiz\Core\Entities\Role', (int) $roleId);

        if ($user !== null && $role !== null) {
            $this->assignation['user'] = $user;
            $this->assignation['role'] = $role;

            $form = $this->buildRemoveRoleForm($user, $role);
            $form->handleRequest();

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
        } else {
            return $this->throw404();
        }
    }

    /**
     * @param array                       $data
     * @param RZ\Roadiz\Core\Entities\User $user
     *
     * @return RZ\Roadiz\Core\Entities\Role
     */
    private function addUserRole($data, User $user)
    {
        if ($data['userId'] == $user->getId()) {
            $role = $this->getService('em')
                         ->find('RZ\Roadiz\Core\Entities\Role', $data['roleId']);

            $user->addRole($role);
            $this->getService('em')->flush();

            return $role;
        }

        return null;
    }

    /**
     * @param array                       $data
     * @param RZ\Roadiz\Core\Entities\User $user
     *
     * @return RZ\Roadiz\Core\Entities\Role
     */
    private function removeUserRole($data, User $user)
    {
        if ($data['userId'] == $user->getId()) {
            $role = $this->getService('em')
                         ->find('RZ\Roadiz\Core\Entities\Role', $data['roleId']);

            if ($role !== null) {
                $user->removeRole($role);
                $this->getService('em')->flush();
            }

            return $role;
        }

        return null;
    }

    /**
     * @param RZ\Roadiz\Core\Entities\User $user
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildEditRolesForm(User $user)
    {
        $defaults = [
            'userId' => $user->getId(),
        ];
        $builder = $this->getService('formFactory')
                        ->createBuilder('form', $defaults)
                        ->add(
                            'userId',
                            'hidden',
                            [
                                'data' => $user->getId(),
                                'constraints' => [
                                    new NotBlank(),
                                ],
                            ]
                        )
                        ->add(
                            'roleId',
                            new \RZ\Roadiz\CMS\Forms\RolesType($user->getRolesEntities()),
                            ['label' => 'Role']
                        );

        return $builder->getForm();
    }

    /**
     * @param RZ\Roadiz\Core\Entities\User $user
     * @param RZ\Roadiz\Core\Entities\Role $role
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildRemoveRoleForm(User $user, Role $role)
    {
        $builder = $this->getService('formFactory')
                        ->createBuilder('form')
                        ->add(
                            'userId',
                            'hidden',
                            [
                                'data' => $user->getId(),
                                'constraints' => [
                                    new NotBlank(),
                                ],
                            ]
                        )
                        ->add(
                            'roleId',
                            'hidden',
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
