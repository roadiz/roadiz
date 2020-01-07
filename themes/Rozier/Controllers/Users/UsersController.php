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
 *
 * @file UsersController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers\Users;

use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Validator\Constraints\NotBlank;
use Themes\Rozier\Forms\AddUserType;
use Themes\Rozier\Forms\UserDetailsType;
use Themes\Rozier\Forms\UserType;
use Themes\Rozier\RozierApp;
use Themes\Rozier\Utils\SessionListFilters;

/**
 * Class UsersController
 * @package Themes\Rozier\Controllers\Users
 */
class UsersController extends RozierApp
{
    /**
     * List every users.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_USERS');

        /*
         * Manage get request to filter list
         */
        $listManager = $this->createEntityListManager(
            User::class,
            [],
            ['username' => 'ASC']
        );
        $listManager->setDisplayingNotPublishedNodes(true);
        /*
         * Stored in session
         */
        $sessionListFilter = new SessionListFilters('user_item_per_page');
        $sessionListFilter->handleItemPerPage($request, $listManager);
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['users'] = $listManager->getEntities();

        return $this->render('users/list.html.twig', $this->assignation);
    }

    /**
     * Return an edition form for requested user.
     *
     * @param Request $request
     * @param int $userId
     *
     * @return Response
     */
    public function editAction(Request $request, $userId)
    {
        $this->denyAccessUnlessGranted('ROLE_BACKEND_USER');

        if (!(
            $this->isGranted('ROLE_ACCESS_USERS') ||
            ($this->getUser() instanceof User && $this->getUser()->getId() == $userId)
        )) {
            throw $this->createAccessDeniedException("You don't have access to this page: ROLE_ACCESS_USERS");
        }
        /** @var User $user */
        $user = $this->get('em')
                     ->find(User::class, (int) $userId);

        if ($user !== null) {
            if (!$this->isGranted(Role::ROLE_SUPERADMIN) && $user->isSuperAdmin()) {
                throw $this->createAccessDeniedException("You cannot edit a super admin.");
            }

            $this->assignation['user'] = $user;

            $form = $this->createForm(UserType::class, $user, [
                'em' => $this->get('em'),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
            ]);

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $this->get('em')->flush();

                $msg = $this->getTranslator()->trans(
                    'user.%name%.updated',
                    ['%name%' => $user->getUsername()]
                );
                $this->publishConfirmMessage($request, $msg);
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl(
                    'usersEditPage',
                    ['userId' => $user->getId()]
                ));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('users/edit.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Return an edition form for requested user details.
     *
     * @param Request $request
     * @param int $userId
     *
     * @return Response
     */
    public function editDetailsAction(Request $request, $userId)
    {
        $this->denyAccessUnlessGranted('ROLE_BACKEND_USER');

        if (!(
            $this->isGranted('ROLE_ACCESS_USERS') ||
            ($this->getUser() instanceof User && $this->getUser()->getId() == $userId)
        )) {
            throw $this->createAccessDeniedException("You don't have access to this page: ROLE_ACCESS_USERS");
        }
        /** @var User $user */
        $user = $this->get('em')->find(User::class, (int) $userId);

        if ($user !== null) {
            if (!$this->isGranted(Role::ROLE_SUPERADMIN) && $user->isSuperAdmin()) {
                throw $this->createAccessDeniedException("You cannot edit a super admin.");
            }
            $this->assignation['user'] = $user;
            $form = $this->createForm(UserDetailsType::class, $user);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                /*
                 * If pictureUrl is empty, use default Gravatar image.
                 */
                if ($user->getPictureUrl() == '') {
                    $user->setPictureUrl($user->getGravatarUrl());
                }

                $this->get('em')->flush();

                $msg = $this->getTranslator()->trans(
                    'user.%name%.updated',
                    ['%name%' => $user->getUsername()]
                );
                $this->publishConfirmMessage($request, $msg);

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl(
                    'usersEditDetailsPage',
                    ['userId' => $user->getId()]
                ));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('users/editDetails.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Return an creation form for requested user.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function addAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_USERS');

        $user = new User();
        $user->sendCreationConfirmationEmail(true);

        if ($user !== null) {
            $this->assignation['user'] = $user;

            $form = $this->createForm(AddUserType::class, $user, [
                'em' => $this->get('em'),
                'authorizationChecker' => $this->get('securityAuthorizationChecker')
            ]);

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $this->get('em')->persist($user);
                $this->get('em')->flush();

                $msg = $this->getTranslator()->trans('user.%name%.created', ['%name%' => $user->getUsername()]);
                $this->publishConfirmMessage($request, $msg);

                return $this->redirect($this->generateUrl('usersHomePage'));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('users/add.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Return a deletion form for requested user.
     *
     * @param Request $request
     * @param int $userId
     *
     * @return Response
     */
    public function deleteAction(Request $request, $userId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_USERS_DELETE');

        /** @var User $user */
        $user = $this->get('em')->find(User::class, (int) $userId);

        if ($user !== null) {
            if (!$this->isGranted(Role::ROLE_SUPERADMIN) && $user->isSuperAdmin()) {
                throw $this->createAccessDeniedException("You cannot edit a super admin.");
            }
            $this->assignation['user'] = $user;
            $form = $this->buildDeleteForm($user);

            $form->handleRequest($request);

            if ($form->isSubmitted() &&
                $form->isValid() &&
                $form->getData()['userId'] == $user->getId()) {
                $this->deleteUser($form->getData(), $user);
                $msg = $this->getTranslator()->trans(
                    'user.%name%.deleted',
                    ['%name%' => $user->getUsername()]
                );
                $this->publishConfirmMessage($request, $msg);
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl('usersHomePage'));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('users/delete.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * @param array $data
     * @param User  $user
     */
    private function deleteUser($data, User $user)
    {
        $this->get('em')->remove($user);
        $this->get('em')->flush();
    }

    /**
     * @param User $user
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    private function buildDeleteForm(User $user)
    {
        $builder = $this->createFormBuilder()
                        ->add(
                            'userId',
                            HiddenType::class,
                            [
                                'data' => $user->getId(),
                                'constraints' => [
                                    new NotBlank(),
                                ],
                            ]
                        );

        return $builder->getForm();
    }
}
