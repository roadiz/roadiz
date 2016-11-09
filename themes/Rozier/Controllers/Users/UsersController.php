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

use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\Utils\MediaFinders\FacebookPictureFinder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;
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
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_USERS');

        /*
         * Manage get request to filter list
         */
        $listManager = $this->createEntityListManager(
            'RZ\Roadiz\Core\Entities\User',
            [],
            ['username' => 'ASC']
        );
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
     * @param int     $userId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $userId)
    {
        $this->validateAccessForRole('ROLE_BACKEND_USER');

        if (!($this->isGranted('ROLE_ACCESS_USERS')
            || $this->getUser()->getId() == $userId)) {
            throw $this->createAccessDeniedException("You don't have access to this page: ROLE_ACCESS_USERS");
        }

        $user = $this->get('em')
                     ->find('RZ\Roadiz\Core\Entities\User', (int) $userId);

        if ($user !== null) {
            $this->assignation['user'] = $user;

            $form = $this->createForm(new UserType(), $user, [
                'em' => $this->get('em'),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
            ]);

            $form->handleRequest($request);

            if ($form->isValid()) {
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
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return an edition form for requested user details.
     *
     * @param Request $request
     * @param int     $userId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editDetailsAction(Request $request, $userId)
    {
        $this->validateAccessForRole('ROLE_BACKEND_USER');

        if (!($this->isGranted('ROLE_ACCESS_USERS')
            || $this->getUser()->getId() == $userId)) {
            throw $this->createAccessDeniedException("You don't have access to this page: ROLE_ACCESS_USERS");
        }

        $user = $this->get('em')
                     ->find('RZ\Roadiz\Core\Entities\User', (int) $userId);

        if ($user !== null) {
            $this->assignation['user'] = $user;

            $form = $this->createForm(new UserDetailsType(), $user);

            $form->handleRequest($request);

            if ($form->isValid()) {
                $this->updateProfileImage($user);
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
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return an creation form for requested user.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_USERS');

        $user = new User();

        if ($user !== null) {
            $this->assignation['user'] = $user;

            $form = $this->createForm(new UserType(), $user, [
                'em' => $this->get('em'),
            ]);

            $form->handleRequest($request);

            if ($form->isValid()) {
                $this->updateProfileImage($user);
                $this->get('em')->persist($user);
                $this->get('em')->flush();

                $user->getViewer()->sendSignInConfirmation();

                $msg = $this->getTranslator()->trans('user.%name%.created', ['%name%' => $user->getUsername()]);
                $this->publishConfirmMessage($request, $msg);

                return $this->redirect($this->generateUrl('usersHomePage'));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('users/add.html.twig', $this->assignation);
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return a deletion form for requested user.
     *
     * @param Request $request
     * @param int     $userId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $userId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_USERS_DELETE');

        $user = $this->get('em')
                     ->find('RZ\Roadiz\Core\Entities\User', (int) $userId);

        if ($user !== null) {
            $this->assignation['user'] = $user;

            $form = $this->buildDeleteForm($user);

            $form->handleRequest($request);

            if ($form->isValid() &&
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
        } else {
            return $this->throw404();
        }
    }
    /**
     * @param User $user
     */
    private function updateProfileImage(User $user)
    {
        if ($user->getFacebookName() != '') {
            try {
                $facebook = new FacebookPictureFinder($user->getFacebookName());
                $url = $facebook->getPictureUrl();
                $user->setPictureUrl($url);
            } catch (\Exception $e) {
                $user->setPictureUrl('');
            }
        }
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
     * @return \Symfony\Component\Form\Form
     */
    private function buildDeleteForm(User $user)
    {
        $builder = $this->createFormBuilder()
                        ->add(
                            'userId',
                            'hidden',
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
