<?php 

namespace Themes\Rozier\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Role;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\ListManagers\EntityListManager;

use Themes\Rozier\RozierApp;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\NotBlank;

use RZ\Renzo\Core\Exceptions\EntityAlreadyExistsException;
use RZ\Renzo\Core\Exceptions\EntityRequiredException;

/**
* 
*/
class RolesController extends RozierApp {

	/**
	 * @param  Symfony\Component\HttpFoundation\Request $request
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function indexAction(Request $request) {

		$listManager = new EntityListManager( 
			$request, 
			Kernel::getInstance()->em(), 
			'RZ\Renzo\Core\Entities\Role',
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
	 * @param Symfony\Component\HttpFoundation\Request $request
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function addAction(Request $request) {
		$form = $this->buildAddForm();
		$form->handleRequest();

		if ($form->isValid()) {

			try {
		 		$role = $this->addRole($form->getData());
		 		$msg = $this->getTranslator()->trans('role.created', array('%name%'=>$role->getName()));
				$request->getSession()->getFlashBag()->add('confirm', $msg);
	 			$this->getLogger()->info($msg);
	 			
			}
			catch(EntityAlreadyExistsException $e){
				$request->getSession()->getFlashBag()->add('error', $e->getMessage());
	 			$this->getLogger()->warning($e->getMessage());
			}
			catch(\RuntimeException $e){
				$request->getSession()->getFlashBag()->add('error', $e->getMessage());
	 			$this->getLogger()->warning($e->getMessage());
			}

	 		/*
	 		 * Force redirect to avoid resending form when refreshing page
	 		 */
	 		$response = new RedirectResponse(
				Kernel::getInstance()->getUrlGenerator()->generate('rolesHomePage')
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
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  int  $role_id
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function deleteAction(Request $request, $role_id) {
		$role = Kernel::getInstance()->em()
					->find('RZ\Renzo\Core\Entities\Role', (int)$role_id);
		if ($role !== null) {

			$form = $this->buildDeleteForm( $role );
			$form->handleRequest();

			if ($form->isValid() && 
				$form->getData()['role_id'] == $role->getId()) {

				try {
			 		$this->deleteRole($form->getData(), $role);
			 		$msg = $this->getTranslator()->trans('role.deleted', array('%name%'=>$role->getName()));
					$request->getSession()->getFlashBag()->add('confirm', $msg);
		 			$this->getLogger()->info($msg);
		 			
				}
				catch(EntityRequiredException $e){
					$request->getSession()->getFlashBag()->add('error', $e->getMessage());
		 			$this->getLogger()->warning($e->getMessage());
				}
				catch(\RuntimeException $e){
					$request->getSession()->getFlashBag()->add('error', $e->getMessage());
		 			$this->getLogger()->warning($e->getMessage());
				}

		 		/*
		 		 * Force redirect to avoid resending form when refreshing page
		 		 */
		 		$response = new RedirectResponse(
					Kernel::getInstance()->getUrlGenerator()->generate('rolesHomePage')
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
		}
		else {
			return $this->throw404();
		}
	}

	/**
	 * Return an edition form for requested role.
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  int  $role_id
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function editAction(Request $request, $role_id) {
		$role = Kernel::getInstance()->em()
					->find('RZ\Renzo\Core\Entities\Role', (int)$role_id);

		if ($role !== null && 
			!$role->required()) {

			$form = $this->buildEditForm( $role );
			$form->handleRequest();

			if ($form->isValid() && 
				$form->getData()['role_id'] == $role->getId()) {

				try {
			 		$this->editRole($form->getData(), $role);
			 		$msg = $this->getTranslator()->trans('role.updated', array('%name%'=>$role->getName()));
					$request->getSession()->getFlashBag()->add('confirm', $msg);
		 			$this->getLogger()->info($msg);
		 			
				}
				catch(EntityAlreadyExistsException $e){
					$request->getSession()->getFlashBag()->add('error', $e->getMessage());
		 			$this->getLogger()->warning($e->getMessage());
				}
				catch(\RuntimeException $e){
					$request->getSession()->getFlashBag()->add('error', $e->getMessage());
		 			$this->getLogger()->warning($e->getMessage());
				}

		 		/*
		 		 * Force redirect to avoid resending form when refreshing page
		 		 */
		 		$response = new RedirectResponse(
					Kernel::getInstance()->getUrlGenerator()->generate('rolesHomePage')
				);
				$response->prepare($request);

				return $response->send();
			}

			$this->assignation['form'] = $form->createView();

			return new Response(
				$this->getTwig()->render('roles/edit.html.twig', $this->assignation),
				Response::HTTP_OK,
				array('content-type' => 'text/html')
			);
		}
		else {
			return $this->throw404();
		}
	}

	/**
	 * Build add role form with name constraint.
	 * @return \Symfony\Component\Form\Form
	 */
	protected function buildAddForm() {
		$builder = $this->getFormFactory()
			->createBuilder('form')
			->add('name', 'text', array(
				'label' => $this->getTranslator()->trans('role.name'),
				'constraints' => array(
					new Regex(array(
						'pattern' => '#^ROLE_([A-Z\_]+)$#',
						'message' => $this->getTranslator()->trans('Role definition must be prefixed with “ROLE_” and contains only uppercase letters and underscores.')
					))
				)
			))
		;

		return $builder->getForm();
	}

	/**
	 * Build delete role form with name constraint.
	 * @param RZ\Renzo\Core\Entities\Role  $role
	 * @return \Symfony\Component\Form\Form
	 */
	protected function buildDeleteForm(Role $role) {
		$builder = $this->getFormFactory()
			->createBuilder('form')
			->add('role_id', 'hidden', array(
				'data'=>$role->getId(),
				'constraints' => array(
					new NotBlank()
				)
			))
		;

		return $builder->getForm();
	}

	/**
	 * Build edit role form with name constraint.
	 * @param  RZ\Renzo\Core\Entities\Role  $role
	 * @return \Symfony\Component\Form\Form
	 */
	protected function buildEditForm(Role $role) {
		$defaults = array(
			'name'=>$role->getName()
		);
		$builder = $this->getFormFactory()
			->createBuilder('form', $defaults)
			->add('role_id', 'hidden', array(
				'data'=>$role->getId(),
				'constraints' => array(
					new NotBlank()
				)
			))
			->add('name', 'text', array(
				'data'=>$role->getName(),
				'constraints' => array(
					new Regex(array(
						'pattern' => '#^ROLE_([A-Z\_]+)$#',
						'message' => $this->getTranslator()->trans('Role definition must be prefixed with “ROLE_” and contains only uppercase letters and underscores.')
					))
				)
			))
		;

		return $builder->getForm();
	}

	/**
	 * @param array  $data
	 * @return RZ\Renzo\Core\Entities\Role
	 */
	protected function addRole(array $data) {
		if (isset($data['name'])) {
			$existing = Kernel::getInstance()->em()
					->getRepository('RZ\Renzo\Core\Entities\Role')
					->findOneBy(array('name' => $data['name']));
			if ($existing !== null) {
				throw new EntityAlreadyExistsException($this->getTranslator()->trans("role.name.already.exists"), 1);
			}

			$role = new Role($data['name']);
			Kernel::getInstance()->em()->persist($role);
			Kernel::getInstance()->em()->flush();

			return $role;
		}
		else {
			throw new \RuntimeException("Role name is not defined", 1);
		}
		return null;
	}

	/**
	 * @param array  $data
	 * @return RZ\Renzo\Core\Entities\Role
	 */
	protected function editRole(array $data, Role $role) {	
		if ($role->required()) {
			throw new EntityRequiredException($this->getTranslator()->trans("role.required.cannot_be_updated"), 1);
		}

		if (isset($data['name'])) {
			$existing = Kernel::getInstance()->em()
					->getRepository('RZ\Renzo\Core\Entities\Role')
					->findOneBy(array('name' => $data['name']));
			if ($existing !== null && 
				$existing->getId() != $role->getId()) {
				throw new EntityAlreadyExistsException($this->getTranslator()->trans("role.name.already.exists"), 1);
			}

			$role->setName($data['name']);
			Kernel::getInstance()->em()->flush();

			return $role;
		}
		else {
			throw new \RuntimeException("Role name is not defined", 1);
		}
		return null;
	}

	/**
	 * @param  array  $data
	 * @param  RZ\Renzo\Core\Entities\Role  $role
	 * @return void
	 */
	protected function deleteRole( array $data, Role $role ) {
		if (!$role->required()) {
			Kernel::getInstance()->em()->remove($role);
			Kernel::getInstance()->em()->flush();
		}
		else {
			throw new EntityRequiredException($this->getTranslator()->trans("role.is.required"), 1);
		}
	}
}