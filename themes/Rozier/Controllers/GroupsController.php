<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file GroupsController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Role;
use RZ\Renzo\Core\Entities\Group;
use RZ\Renzo\Core\Entities\User;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\ListManagers\EntityListManager;
use RZ\Renzo\Core\Exceptions\EntityAlreadyExistsException;
use RZ\Renzo\Core\Exceptions\EntityRequiredException;
use Themes\Rozier\RozierApp;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * {@inheritdoc}
 */
class GroupsController extends RozierApp
{
    /**
     * List groups action.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        /*
         * Manage get request to filter list
         */
        $listManager = new EntityListManager(
            $request,
            $this->getKernel()->em(),
            'RZ\Renzo\Core\Entities\Group'
        );
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['groups'] = $listManager->getEntities();

        return new Response(
            $this->getTwig()->render('groups/list.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request)
    {
        $form = $this->buildAddForm();
        $form->handleRequest();

        if ($form->isValid()) {

            try {
                $group = $this->addGroup($form->getData());
                $msg = $this->getTranslator()->trans('group.created', array('%name%'=>$group->getName()));
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getLogger()->info($msg);

            } catch (EntityAlreadyExistsException $e) {
                $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                $this->getLogger()->warning($e->getMessage());
            } catch (\RuntimeException $e) {
                $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                $this->getLogger()->warning($e->getMessage());
            }

            /*
             * Force redirect to avoid resending form when refreshing page
             */
            $response = new RedirectResponse(
                $this->getKernel()->getUrlGenerator()->generate('groupsHomePage')
            );
            $response->prepare($request);

            return $response->send();
        }

        $this->assignation['form'] = $form->createView();

        return new Response(
            $this->getTwig()->render('groups/add.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $groupId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $groupId)
    {
        $group = $this->getKernel()->em()
            ->find('RZ\Renzo\Core\Entities\Group', (int) $groupId);

        if ($group !== null) {
            $form = $this->buildDeleteForm($group);
            $form->handleRequest();

            if ($form->isValid() &&
                $form->getData()['groupId'] == $group->getId()) {

                try {
                    $this->deleteGroup($form->getData(), $group);
                    $msg = $this->getTranslator()->trans('group.deleted', array('%name%' => $group->getName()));
                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getLogger()->info($msg);

                } catch (\RuntimeException $e) {
                    $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                    $this->getLogger()->warning($e->getMessage());
                }

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getKernel()->getUrlGenerator()->generate('groupsHomePage')
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('groups/delete.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );

        } else {
            return $this->throw404();
        }
    }

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $groupId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $groupId)
    {
        $group = $this->getKernel()->em()
                    ->find('RZ\Renzo\Core\Entities\Group', (int) $groupId);

        if ($group !== null) {
            $this->assignation['group'] = $group;

            $form = $this->buildEditForm($group);
            $form->handleRequest();

            if ($form->isValid() &&
                $form->getData()['groupId'] == $group->getId()) {

                try {
                    $this->editGroup($form->getData(), $group);
                    $msg = $this->getTranslator()->trans('group.updated', array('%name%'=>$group->getName()));
                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getLogger()->info($msg);

                } catch (EntityAlreadyExistsException $e) {
                    $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                    $this->getLogger()->warning($e->getMessage());
                } catch (\RuntimeException $e) {
                    $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                    $this->getLogger()->warning($e->getMessage());
                }

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getKernel()->getUrlGenerator()->generate('groupsHomePage')
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('groups/edit.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return an edition form for requested group.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $groupId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function editRolesAction(Request $request, $groupId)
    {
        $group = $this->getKernel()->em()
            ->find('RZ\Renzo\Core\Entities\Group', (int) $groupId);

        if ($group !== null) {

            $this->assignation['group'] = $group;
            $form = $this->buildEditRolesForm($group);
            $form->handleRequest();

            if ($form->isValid()) {
                $role = $this->addRole($form->getData(), $group);

                $msg = $this->getTranslator()->trans('group.role_linked', array(
                            '%group%'=>$group->getName(),
                            '%role%'=>$role->getName()
                        ));
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getLogger()->info($msg);

                /*
                * Force redirect to avoid resending form when refreshing page
                */
                $response = new RedirectResponse(
                    $this->getKernel()->getUrlGenerator()->generate(
                        'groupsEditRolesPage',
                        array('groupId' => $group->getId())
                    )
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('groups/roles.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $groupId
     * @param int                                      $roleId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function removeRolesAction(Request $request, $groupId, $roleId)
    {
        $group = $this->getKernel()->em()
            ->find('RZ\Renzo\Core\Entities\Group', (int) $groupId);
        $role = $this->getKernel()->em()
            ->find('RZ\Renzo\Core\Entities\Role', (int) $roleId);

        if ($group !== null &&
            $role !== null) {
            $this->assignation['group'] = $group;
            $this->assignation['role'] = $role;

            $form = $this->buildRemoveRoleForm($group, $role);
            $form->handleRequest();

            if ($form->isValid()) {

                $this->removeRole($form->getData(), $group, $role);
                $msg = $this->getTranslator()->trans('role.removed_from_group', array('%name%'=>$role->getName()));
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getLogger()->info($msg);

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getKernel()->getUrlGenerator()->generate(
                        'groupsEditRolesPage',
                        array('groupId' => $group->getId())
                    )
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('groups/removeRole.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $groupId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function editUsersAction(Request $request, $groupId)
    {
        $group = $this->getKernel()->em()
            ->find('RZ\Renzo\Core\Entities\Group', (int) $groupId);

        if ($group !== null) {

            $this->assignation['group'] = $group;
            $form = $this->buildEditUsersForm($group);
            $form->handleRequest();

            if ($form->isValid()) {
                $user = $this->addUser($form->getData(), $group);

                $msg = $this->getTranslator()->trans('group.user_linked', array(
                            '%group%'=>$group->getName(),
                            '%user%'=>$user->getUserName()
                        ));
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getLogger()->info($msg);

                /*
                * Force redirect to avoid resending form when refreshing page
                */
                $response = new RedirectResponse(
                    $this->getKernel()->getUrlGenerator()->generate(
                        'groupsEditUsersPage',
                        array('groupId' => $group->getId())
                    )
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('groups/users.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $groupId
     * @param int                                      $userId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function removeUsersAction(Request $request, $groupId, $userId)
    {
        $group = $this->getKernel()->em()
            ->find('RZ\Renzo\Core\Entities\Group', (int) $groupId);
        $user = $this->getKernel()->em()
            ->find('RZ\Renzo\Core\Entities\User', (int) $userId);

        if ($group !== null &&
            $user !== null ) {
            $this->assignation['group'] = $group;
            $this->assignation['user'] = $user;

            $form = $this->buildRemoveUserForm($group, $user);
            $form->handleRequest();

            if ($form->isValid()) {

                $this->removeUser($form->getData(), $group, $user);
                $msg = $this->getTranslator()->trans('group.user.removed_from_group', array('%name%'=>$user->getUserName()));
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getLogger()->info($msg);

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getKernel()->getUrlGenerator()->generate(
                        'groupsEditUsersPage',
                        array('groupId' => $group->getId(),
                            'userId' => $user->getId())
                    )
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('groups/removeUser.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
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
        $builder = $this->getFormFactory()
            ->createBuilder('form')
            ->add('name', 'text', array(
                'label' => $this->getTranslator()->trans('group.name'),
                'constraints' => array(
                    new NotBlank()
                )
            ));

        return $builder->getForm();
    }

    /**
     * Build edit group form with name constraint.
     *
     * @param RZ\Renzo\Core\Entities\Group $group
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function buildEditForm(Group $group)
    {
        $defaults = array(
            'name'=>$group->getName()
        );
        $builder = $this->getFormFactory()
            ->createBuilder('form', $defaults)
            ->add('groupId', 'hidden', array(
                'data'=>$group->getId(),
                'constraints' => array(
                    new NotBlank()
                )
            ))
            ->add('name', 'text', array(
                'data'=>$group->getName(),
                'constraints' => array(
                    new NotBlank()
                )
            ));

        return $builder->getForm();
    }

    /**
     * Build delete group form with name constraint.
     *
     * @param RZ\Renzo\Core\Entities\Group $group
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function buildDeleteForm(Group $group)
    {
        $builder = $this->getFormFactory()
           ->createBuilder('form')
            ->add('groupId', 'hidden', array(
                'data'=>$group->getId(),
                'constraints' => array(
                    new NotBlank()
                )
            ));

        return $builder->getForm();
    }

    /**
     * @param RZ\Renzo\Core\Entities\Group $group
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildEditRolesForm(Group $group)
    {
        $defaults = array(
            'groupId' =>  $group->getId()
        );
        $builder = $this->getFormFactory()
            ->createBuilder('form', $defaults)
            ->add('groupId', 'hidden', array(
                'data' => $group->getId(),
                'constraints' => array(
                    new NotBlank()
                )
            ))
            ->add(
                'roleId',
                new \RZ\Renzo\CMS\Forms\RolesType($group->getRolesEntities()),
                array('label' => 'Role')
            );

        return $builder->getForm();
    }

    /**
     * @param RZ\Renzo\Core\Entities\Group $group
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildEditUsersForm(Group $group)
    {
        $defaults = array(
            'groupId' =>  $group->getId()
        );
        $builder = $this->getFormFactory()
            ->createBuilder('form', $defaults)
            ->add('groupId', 'hidden', array(
                'data' => $group->getId(),
                'constraints' => array(
                    new NotBlank()
                )
            ))
            ->add(
                'userId',
                new \RZ\Renzo\CMS\Forms\UsersType($group->getUsers()),
                array(
                    'label' => 'User',
                    'constraints' => array(
                        new NotBlank()
                    )
                )
            );

        return $builder->getForm();
    }

    /**
     * @param RZ\Renzo\Core\Entities\Group $group
     * @param RZ\Renzo\Core\Entities\Role  $role
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildRemoveRoleForm(Group $group, Role $role)
    {
        $builder = $this->getFormFactory()
            ->createBuilder('form')
            ->add('groupId', 'hidden', array(
                'data' => $group->getId(),
                'constraints' => array(
                    new NotBlank()
                )
            ))
            ->add('roleId', 'hidden', array(
                'data' => $role->getId(),
                'constraints' => array(
                    new NotBlank()
                )
            ));

        return $builder->getForm();
    }

    /**
     * @param RZ\Renzo\Core\Entities\Group $group
     * @param RZ\Renzo\Core\Entities\User  $user
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildRemoveUserForm(Group $group, User $user)
    {
        $builder = $this->getFormFactory()
            ->createBuilder('form')
            ->add('groupId', 'hidden', array(
                'data' => $group->getId(),
                'constraints' => array(
                    new NotBlank()
                )
            ))
            ->add('userId', 'hidden', array(
                'data' => $user->getId(),
                'constraints' => array(
                    new NotBlank()
                )
            ));

        return $builder->getForm();
    }

    /**
     * @param array $data
     *
     * @return RZ\Renzo\Core\Entities\Group
     */
    protected function addGroup(array $data)
    {
        if (isset($data['name'])) {
            $existing = $this->getKernel()->em()
                    ->getRepository('RZ\Renzo\Core\Entities\Group')
                    ->findOneBy(array('name' => $data['name']));

            if ($existing !== null) {
                throw new EntityAlreadyExistsException($this->getTranslator()->trans("group.name.already.exists"), 1);
            }

            $group = new Group();
            $group->setName($data['name']);
            $this->getKernel()->em()->persist($group);
            $this->getKernel()->em()->flush();

            return $group;
        } else {
            throw new \RuntimeException("Group name is not defined", 1);
        }

        return null;
    }

    /**
     * @param array $data
     * @param Group $group
     *
     * @return RZ\Renzo\Core\Entities\Group
     * @throws \RuntimeException
     */
    protected function editGroup(array $data, Group $group)
    {
        if (isset($data['name'])) {
            $existing = $this->getKernel()->em()
                    ->getRepository('RZ\Renzo\Core\Entities\Group')
                    ->findOneBy(array('name' => $data['name']));
            if ($existing !== null &&
                $existing->getId() != $group->getId()) {
                throw new EntityAlreadyExistsException($this->getTranslator()->trans("group.name.already.exists"), 1);
            }

            $group->setName($data['name']);
            $this->getKernel()->em()->flush();

            return $group;
        } else {
            throw new \RuntimeException("Group name is not defined", 1);
        }

        return null;
    }

    /**
     * @param array                        $data
     * @param RZ\Renzo\Core\Entities\Group $group
     */
    protected function deleteGroup(array $data, Group $group)
    {
        $this->getKernel()->em()->remove($group);
        $this->getKernel()->em()->flush();
    }

    /**
     * @param array                        $data
     * @param RZ\Renzo\Core\Entities\Group $group
     *
     * @return RZ\Renzo\Core\Entities\User
     */
    private function addRole($data, Group $group)
    {
        if ($data['groupId'] == $group->getId()) {
            $role = $this->getKernel()->em()
                ->find('RZ\Renzo\Core\Entities\Role', (int) $data['roleId']);
            if ($role !== null) {
                $group->addRole($role);
                $this->getKernel()->em()->flush();

                return $role;
            }
        }
    }

    /**
     * @param array                        $data
     * @param RZ\Renzo\Core\Entities\Group $group
     * @param RZ\Renzo\Core\Entities\Role  $role
     *
     * @return RZ\Renzo\Core\Entities\Role
     */
    private function removeRole($data, Group $group, Role $role)
    {
        if ($data['groupId'] == $group->getId() &&
            $data['roleId'] == $role->getId()) {

            if ($role !== null) {
                $group->removeRole($role);
                $this->getKernel()->em()->flush();
            }

            return $role;
        }
    }

    /**
     * @param array                        $data
     * @param RZ\Renzo\Core\Entities\Group $group
     *
     * @return RZ\Renzo\Core\Entities\User
     */
    private function addUser($data, Group $group)
    {
        if ($data['groupId'] == $group->getId()) {
            $user = $this->getKernel()->em()
                ->find('RZ\Renzo\Core\Entities\User', (int) $data['userId']);

            if ($user !== null) {
                //$group->addUser($user);
                $user->addGroup($group);
                $this->getKernel()->em()->flush();

                return $user;
            }
        }
    }

    /**
     * @param array                        $data
     * @param RZ\Renzo\Core\Entities\Group $group
     * @param RZ\Renzo\Core\EntitiesUser   $user
     *
     * @return RZ\Renzo\Core\Entities\User
     */
    private function removeUser($data, Group $group, User $user)
    {
        if ($data['groupId'] == $group->getId() &&
            $data['userId'] == $user->getId()) {

            if ($user !== null) {
                $user->removeGroup($group);
                $this->getKernel()->em()->flush();
            }

            return $user;
        }
    }
}
