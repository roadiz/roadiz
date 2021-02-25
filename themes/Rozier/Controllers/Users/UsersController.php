<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers\Users;

use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Themes\Rozier\Forms\AddUserType;
use Themes\Rozier\Forms\UserDetailsType;
use Themes\Rozier\Forms\UserType;
use Themes\Rozier\RozierApp;
use Themes\Rozier\Utils\SessionListFilters;

/**
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
     * @param int     $userId
     *
     * @return Response
     */
    public function editAction(Request $request, int $userId)
    {
        $this->denyAccessUnlessGranted('ROLE_BACKEND_USER');

        if (!(
            $this->isGranted('ROLE_ACCESS_USERS') ||
            ($this->getUser() instanceof User && $this->getUser()->getId() == $userId)
        )) {
            throw $this->createAccessDeniedException("You don't have access to this page: ROLE_ACCESS_USERS");
        }
        $user = $this->get('em')->find(User::class, $userId);

        if ($user !== null) {
            if (!$this->isGranted(Role::ROLE_SUPERADMIN) && $user->isSuperAdmin()) {
                throw $this->createAccessDeniedException("You cannot edit a super admin.");
            }

            $this->assignation['user'] = $user;

            $form = $this->createForm(UserType::class, $user);
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
    public function editDetailsAction(Request $request, int $userId)
    {
        $this->denyAccessUnlessGranted('ROLE_BACKEND_USER');

        if (!(
            $this->isGranted('ROLE_ACCESS_USERS') ||
            ($this->getUser() instanceof User && $this->getUser()->getId() == $userId)
        )) {
            throw $this->createAccessDeniedException("You don't have access to this page: ROLE_ACCESS_USERS");
        }
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
        $this->assignation['user'] = $user;

        $form = $this->createForm(AddUserType::class, $user);
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

    /**
     * Return a deletion form for requested user.
     *
     * @param Request $request
     * @param int $userId
     *
     * @return Response
     */
    public function deleteAction(Request $request, int $userId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_USERS_DELETE');
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
                                    new NotNull(),
                                    new NotBlank(),
                                ],
                            ]
                        );

        return $builder->getForm();
    }
}
