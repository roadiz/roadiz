<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file RolesController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\ListManagers\EntityListManager;

use Themes\Rozier\RozierApp;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\NotBlank;

use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use RZ\Roadiz\Core\Exceptions\EntityRequiredException;

/**
 * {@inheritdoc}
 */
class RolesController extends RozierApp
{

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_ROLES');

        $listManager = new EntityListManager(
            $request,
            $this->getService('em'),
            'RZ\Roadiz\Core\Entities\Role',
            array(),
            array('name' => 'ASC')
        );
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['roles'] = $listManager->getEntities();

        return new Response(
            $this->getTwig()->render('roles/list.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * Return an creation form for requested role.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_ROLES');

        $form = $this->buildAddForm();
        $form->handleRequest();

        if ($form->isValid()) {

            try {
                $role = $this->addRole($form->getData());
                $msg = $this->getTranslator()->trans('role.%name%.created', array('%name%'=>$role->getName()));
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getService('logger')->info($msg);

            } catch (EntityAlreadyExistsException $e) {
                $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                $this->getService('logger')->warning($e->getMessage());
            } catch (\RuntimeException $e) {
                $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                $this->getService('logger')->warning($e->getMessage());
            }

            /*
             * Force redirect to avoid resending form when refreshing page
             */
            $response = new RedirectResponse(
                $this->getService('urlGenerator')->generate('rolesHomePage')
            );
            $response->prepare($request);

            return $response->send();
        }

        $this->assignation['form'] = $form->createView();

        return new Response(
            $this->getTwig()->render('roles/add.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * Return an deletion form for requested role.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $roleId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $roleId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_ROLES');

        $role = $this->getService('em')
                    ->find('RZ\Roadiz\Core\Entities\Role', (int) $roleId);
        if ($role !== null) {

            $form = $this->buildDeleteForm($role);
            $form->handleRequest();

            if ($form->isValid() &&
                $form->getData()['roleId'] == $role->getId()) {

                try {
                    $this->deleteRole($form->getData(), $role);
                    $msg = $this->getTranslator()->trans('role.%name%.deleted', array('%name%'=>$role->getName()));
                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getService('logger')->info($msg);

                } catch (EntityRequiredException $e) {
                    $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                    $this->getService('logger')->warning($e->getMessage());
                } catch (\RuntimeException $e) {
                    $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                    $this->getService('logger')->warning($e->getMessage());
                }

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate('rolesHomePage')
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('roles/delete.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return an edition form for requested role.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $roleId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $roleId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_ROLES');

        $role = $this->getService('em')
                     ->find('RZ\Roadiz\Core\Entities\Role', (int) $roleId);

        if ($role !== null &&
            !$role->required()) {

            $form = $this->buildEditForm($role);
            $form->handleRequest();

            if ($form->isValid() &&
                $form->getData()['roleId'] == $role->getId()) {

                try {
                    $this->editRole($form->getData(), $role);
                    $msg = $this->getTranslator()->trans('role.%name%.updated', array('%name%'=>$role->getName()));
                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getService('logger')->info($msg);

                } catch (EntityAlreadyExistsException $e) {
                    $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                    $this->getService('logger')->warning($e->getMessage());
                } catch (\RuntimeException $e) {
                    $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                    $this->getService('logger')->warning($e->getMessage());
                }

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate('rolesHomePage')
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();
            $this->assignation['role'] = $role;

            return new Response(
                $this->getTwig()->render('roles/edit.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * Build add role form with name constraint.
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function buildAddForm()
    {
        $builder = $this->getService('formFactory')
            ->createBuilder('form')
            ->add('name', 'text', array(
                'label' => $this->getTranslator()->trans('name'),
                'constraints' => array(
                    new Regex(array(
                        'pattern' => '#^ROLE_([A-Z\_]+)$#',
                        'message' => $this->getTranslator()->trans('role.name.must_comply_with_standard')
                    ))
                )
            ));

        return $builder->getForm();
    }

    /**
     * Build delete role form with name constraint.
     *
     * @param RZ\Roadiz\Core\Entities\Role $role
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function buildDeleteForm(Role $role)
    {
        $builder = $this->getService('formFactory')
            ->createBuilder('form')
            ->add('roleId', 'hidden', array(
                'data'=>$role->getId(),
                'constraints' => array(
                    new NotBlank()
                )
            ));

        return $builder->getForm();
    }

    /**
     * Build edit role form with name constraint.
     *
     * @param RZ\Roadiz\Core\Entities\Role $role
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function buildEditForm(Role $role)
    {
        $defaults = array(
            'name'=>$role->getName()
        );
        $builder = $this->getService('formFactory')
            ->createBuilder('form', $defaults)
            ->add('roleId', 'hidden', array(
                'data'=>$role->getId(),
                'constraints' => array(
                    new NotBlank()
                )
            ))
            ->add('name', 'text', array(
                'data'=>$role->getName(),
                'label' => $this->getTranslator()->trans('name'),
                'constraints' => array(
                    new Regex(array(
                        'pattern' => '#^ROLE_([A-Z\_]+)$#',
                        'message' => $this->getTranslator()->trans('role.name.must_comply_with_standard')
                    ))
                )
            ));

        return $builder->getForm();
    }

    /**
     * @param array $data
     *
     * @return RZ\Roadiz\Core\Entities\Role
     */
    protected function addRole(array $data)
    {
        if (isset($data['name'])) {
            $existing = $this->getService('em')
                    ->getRepository('RZ\Roadiz\Core\Entities\Role')
                    ->findOneBy(array('name' => $data['name']));
            if ($existing !== null) {
                throw new EntityAlreadyExistsException($this->getTranslator()->trans("role.name.already.exists"), 1);
            }

            $role = new Role($data['name']);
            $this->getService('em')->persist($role);
            $this->getService('em')->flush();

            // Clear result cache
            $cacheDriver = Kernel::getService('em')->getConfiguration()->getResultCacheImpl();
            if ($cacheDriver !== null) {
                $cacheDriver->deleteAll();
            }

            return $role;
        } else {
            throw new \RuntimeException("Role name is not defined", 1);
        }

        return null;
    }

    /**
     * @param array                       $data
     * @param RZ\Roadiz\Core\Entities\Role $role
     *
     * @return RZ\Roadiz\Core\Entities\Role
     */
    protected function editRole(array $data, Role $role)
    {
        if ($role->required()) {
            throw new EntityRequiredException($this->getTranslator()->trans("role.required.cannot_be_updated"), 1);
        }

        if (isset($data['name'])) {
            $existing = $this->getService('em')
                    ->getRepository('RZ\Roadiz\Core\Entities\Role')
                    ->findOneBy(array('name' => $data['name']));
            if ($existing !== null &&
                $existing->getId() != $role->getId()) {
                throw new EntityAlreadyExistsException($this->getTranslator()->trans("role.name.already.exists"), 1);
            }

            $role->setName($data['name']);
            $this->getService('em')->flush();

            // Clear result cache
            $cacheDriver = Kernel::getService('em')->getConfiguration()->getResultCacheImpl();
            if ($cacheDriver !== null) {
                $cacheDriver->deleteAll();
            }

            return $role;
        } else {
            throw new \RuntimeException("Role name is not defined", 1);
        }

        return null;
    }

    /**
     * @param array                        $data
     * @param RZ\Roadiz\Core\Entities\Role  $role
     */
    protected function deleteRole(array $data, Role $role)
    {
        if (!$role->required()) {
            $this->getService('em')->remove($role);
            $this->getService('em')->flush();

            // Clear result cache
            $cacheDriver = Kernel::getService('em')->getConfiguration()->getResultCacheImpl();
            if ($cacheDriver !== null) {
                $cacheDriver->deleteAll();
            }
        } else {
            throw new EntityRequiredException($this->getTranslator()->trans("role.is.required"), 1);
        }
    }
}
