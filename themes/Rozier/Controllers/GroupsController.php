<?php 

namespace Themes\Rozier\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Role;
use RZ\Renzo\Core\Entities\Group;
use RZ\Renzo\Core\Entities\User;
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
class GroupsController extends RozierApp {
	
	const ITEM_PER_PAGE = 5;
	
	/**
	 * List groups action.
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function indexAction(Request $request)
	{
		/*
		 * Manage get request to filter list
		 */
		$listManager = new EntityListManager( 
			$request, 
			Kernel::getInstance()->em(), 
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
	 * @param Symfony\Component\HttpFoundation\Request  $request
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function addAction( Request $request )
	{
		$form = $this->buildAddForm();
		$form->handleRequest();

		if ($form->isValid()) {

			try {
		 		$group = $this->addGroup($form->getData());
		 		$msg = $this->getTranslator()->trans('group.created', array('%name%'=>$group->getName()));
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
				Kernel::getInstance()->getUrlGenerator()->generate('groupsHomePage')
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
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  int  $group_id
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function deleteAction(Request $request, $group_id) {
		$group = Kernel::getInstance()->em()
			->find('RZ\Renzo\Core\Entities\Group', (int)$group_id);
		if ($group !== null) {

			$form = $this->buildDeleteForm( $group );
			$form->handleRequest();

			if ($form->isValid() && 
				$form->getData()['group_id'] == $group->getId()) {

				try {
			 		$this->deleteGroup($form->getData(), $group);
			 		$msg = $this->getTranslator()->trans('group.deleted', array('%name%' => $group->getName()));
					$request->getSession()->getFlashBag()->add('confirm', $msg);
		 			$this->getLogger()->info($msg);
		 			
				}
				catch (\RuntimeException $e) {
					$request->getSession()->getFlashBag()->add('error', $e->getMessage());
		 			$this->getLogger()->warning($e->getMessage());
				}

		 		/*
		 		 * Force redirect to avoid resending form when refreshing page
		 		 */
		 		$response = new RedirectResponse (
					Kernel::getInstance()->getUrlGenerator()->generate('groupsHomePage')
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
		}
		else {
			return $this->throw404();
		}
	}

	/**
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  int  $group_id
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function editAction(Request $request, $group_id) {
		$group = Kernel::getInstance()->em()
					->find('RZ\Renzo\Core\Entities\Group', (int)$group_id);

		if ($group !== null) {
			$this->assignation['group'] = $group;

			$form = $this->buildEditForm( $group );
			$form->handleRequest();

			if ($form->isValid() && 
				$form->getData()['group_id'] == $group->getId()) {

				try {
			 		$this->editGroup($form->getData(), $group);
			 		$msg = $this->getTranslator()->trans('group.updated', array('%name%'=>$group->getName()));
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
					Kernel::getInstance()->getUrlGenerator()->generate('groupsHomePage')
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
		}
		else {
			return $this->throw404();
		}
	}

	/**
	 * Return an edition form for requested group.
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  int  $group_id
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function editRolesAction(Request $request, $group_id) {
		$group = Kernel::getInstance()->em()
			->find('RZ\Renzo\Core\Entities\Group', (int)$group_id);

		if ($group !== null) {

			$this->assignation['group'] = $group;

			$form = $this->buildEditRolesForm( $group );

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
					Kernel::getInstance()->getUrlGenerator()->generate(
						'groupsEditRolesPage',
						array('group_id' => $group->getId())
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
		}
		else {
			return $this->throw404();
		}
	}

	/**
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  int  $group_id
	 * @param  int  $role_id
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function removeRolesAction(Request $request, $group_id, $role_id) {	
		$group = Kernel::getInstance()->em()
			->find('RZ\Renzo\Core\Entities\Group', (int)$group_id);
		$role = Kernel::getInstance()->em()
			->find('RZ\Renzo\Core\Entities\Role', (int)$role_id);
			
		if ($group !== null && 
			$role !== null ) {
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
					Kernel::getInstance()->getUrlGenerator()->generate(
						'groupsEditRolesPage',
						array('group_id' => $group->getId())
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
		}
		else {
			return $this->throw404();
		}
	}

	/**
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  int  $group_id
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function editUsersAction(Request $request, $group_id) {
		$group = Kernel::getInstance()->em()
			->find('RZ\Renzo\Core\Entities\Group', (int)$group_id);

		if ($group !== null) {

			$this->assignation['group'] = $group;

			$form = $this->buildEditUsersForm( $group );

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
					Kernel::getInstance()->getUrlGenerator()->generate(
						'groupsEditUsersPage',
						array('group_id' => $group->getId())
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
		}
		else {
			return $this->throw404();
		}
	}

	/**
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  int  $group_id
	 * @param  int  $user_id
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function removeUsersAction(Request $request, $group_id, $user_id) {	
		$group = Kernel::getInstance()->em()
			->find('RZ\Renzo\Core\Entities\Group', (int)$group_id);
		$user = Kernel::getInstance()->em()
			->find('RZ\Renzo\Core\Entities\User', (int)$user_id);
			
		if ($group !== null && 
			$user !== null ) {
			$this->assignation['group'] = $group;
			$this->assignation['user'] = $user;

			$form = $this->buildRemoveUserForm($group, $user);
			$form->handleRequest();

			if ($form->isValid()) {

		 		$this->removeUser($form->getData(), $group, $user);
		 		$msg = $this->getTranslator()->trans('user.removed_from_group', array('%name%'=>$user->getUserName()));
		 		$request->getSession()->getFlashBag()->add('confirm', $msg);
	 			$this->getLogger()->info($msg);

		 		/*
		 		 * Force redirect to avoid resending form when refreshing page
		 		 */
		 		$response = new RedirectResponse(
					Kernel::getInstance()->getUrlGenerator()->generate(
						'groupsEditUsersPage',
						array('group_id' => $group->getId(),
							'user_id' => $user->getId())
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
		}
		else {
			return $this->throw404();
		}
	}

	/**
	 * Build add group form with name constraint.
	 * @return Symfony\Component\Form\Forms
	 */
	protected function buildAddForm() {
		$builder = $this->getFormFactory()
			->createBuilder('form')
			->add('name', 'text', array(
				'label' => $this->getTranslator()->trans('group.name'),
				'constraints' => array(
					new NotBlank()
				)
			))
		;

		return $builder->getForm();
	}

	/**
	 * Build delete group form with name constraint.
	 * @param  RZ\Renzo\Core\Entities\Group  $group
	 * @return Symfony\Component\Form\Forms
	 */
	protected function buildDeleteForm(Group $group) {
		$builder = $this->getFormFactory()
			->createBuilder('form')
			->add('group_id', 'hidden', array(
				'data'=>$group->getId(),
				'constraints' => array(
					new NotBlank()
				)
			))
		;

		return $builder->getForm();
	}

	/**
	 * Build edit group form with name constraint.
	 * @param  RZ\Renzo\Core\Entities\Group  $group
	 * @return Symfony\Component\Form\Forms
	 */
	protected function buildEditForm(Group $group) {
		$defaults = array(
			'name'=>$group->getName()
		);
		$builder = $this->getFormFactory()
			->createBuilder('form', $defaults)
			->add('group_id', 'hidden', array(
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
			))
		;

		return $builder->getForm();
	}

	/**
	 * 
	 * @param  RZ\Renzo\Core\Entities\Group $group 
	 * @return Symfony\Component\Form\Forms
	 */
	private function buildEditRolesForm(Group $group) {
		$defaults = array(
			'group_id' =>  $group->getId()
		);
		$builder = $this->getFormFactory()
			->createBuilder('form', $defaults)
			->add('group_id', 'hidden', array(
				'data' => $group->getId(),
				'constraints' => array(
					new NotBlank()
				)
			))
			->add('role_id', new \RZ\Renzo\CMS\Forms\RolesType($group->getRolesEntities()),
				array('label' => 'Role'));

		return $builder->getForm();
	}

	/**
	 * 
	 * @param  RZ\Renzo\Core\Entities\Group $group 
	 * @return Symfony\Component\Form\Forms
	 */
	private function buildEditUsersForm(Group $group) {
		$defaults = array(
			'group_id' =>  $group->getId()
		);
		$builder = $this->getFormFactory()
			->createBuilder('form', $defaults)
			->add('group_id', 'hidden', array(
				'data' => $group->getId(),
				'constraints' => array(
					new NotBlank()
				)
			))
			->add('user_id', new \RZ\Renzo\CMS\Forms\UsersType($group->getUsers()),
				array(
					'label' => 'User',
					'constraints' => array(
						new NotBlank()
					)
			));

		return $builder->getForm();
	}

	/**
	 * @param RZ\Renzo\Core\Entities\Group $group
	 * @param RZ\Renzo\Core\Entities\Role $role
	 * @return Symfony\Component\Form\Forms
	 */
	private function buildRemoveRoleForm(Group $group, Role $role)
	{
		$builder = $this->getFormFactory()
			->createBuilder('form')
			->add('group_id', 'hidden', array(
				'data' => $group->getId(),
				'constraints' => array(
					new NotBlank()
				)
			))
			->add('role_id', 'hidden', array(
				'data' => $role->getId(),
				'constraints' => array(
					new NotBlank()
				)
			))
		;

		return $builder->getForm();
	}

	/**
	 * @param RZ\Renzo\Core\Entities\Group $group
	 * @param RZ\Renzo\Core\Entities\User $user
	 * @return Symfony\Component\Form\Forms
	 */
	private function buildRemoveUserForm(Group $group, User $user)
	{
		$builder = $this->getFormFactory()
			->createBuilder('form')
			->add('group_id', 'hidden', array(
				'data' => $group->getId(),
				'constraints' => array(
					new NotBlank()
				)
			))
			->add('user_id', 'hidden', array(
				'data' => $user->getId(),
				'constraints' => array(
					new NotBlank()
				)
			))
		;

		return $builder->getForm();
	}

	/**
	 * @param array  $data 
	 * @return RZ\Renzo\Core\Entities\Group 
	 */
	protected function addGroup(array $data) {
		if (isset($data['name'])) {
			$existing = Kernel::getInstance()->em()
					->getRepository('RZ\Renzo\Core\Entities\Group')
					->findOneBy(array('name' => $data['name']));
			if ($existing !== null) {
				throw new EntityAlreadyExistsException($this->getTranslator()->trans("group.name.already.exists"), 1);
			}

			$group = new Group();
			$group->setName($data['name']);
			Kernel::getInstance()->em()->persist($group);
			Kernel::getInstance()->em()->flush();

			return $group;
		}
		else {
			throw new \RuntimeException("Group name is not defined", 1);
		}
		return null;
	}

	/**
	 * @param array $data
	 * @return RZ\Renzo\Core\Entities\Group
	 */
	protected function editGroup(array $data, Group $group) {	
		if (isset($data['name'])) {
			$existing = Kernel::getInstance()->em()
					->getRepository('RZ\Renzo\Core\Entities\Group')
					->findOneBy(array('name' => $data['name']));
			if ($existing !== null && 
				$existing->getId() != $group->getId()) {
				throw new EntityAlreadyExistsException($this->getTranslator()->trans("group.name.already.exists"), 1);
			}

			$group->setName($data['name']);
			Kernel::getInstance()->em()->flush();

			return $group;
		}
		else {
			throw new \RuntimeException("Group name is not defined", 1);
		}
		return null;
	}

	/**
	 * @param  array  $data
	 * @param  RZ\Renzo\Core\Entities\Group  $group
	 * @return void
	 */
	protected function deleteGroup(array $data, Group $group) {
		Kernel::getInstance()->em()->remove($group);
		Kernel::getInstance()->em()->flush();
	}

	/**
	 * @param array  $data
	 * @param RZ\Renzo\Core\Entities\Group  $group
	 * @return RZ\Renzo\Core\Entities\User
	 */
	private function addRole($data, Group $group) {
		if ($data['group_id'] == $group->getId()) {
			$role = Kernel::getInstance()->em()
				->find('RZ\Renzo\Core\Entities\Role', (int)$data['role_id']);
			if ($role !== null) {
				$group->addRole($role);
				Kernel::getInstance()->em()->flush();
				
				return ($role);
			}
		}
	}

	/**
	 * @param  array  $data
	 * @param  RZ\Renzo\Core\Entities\Group  $group
	 * @param  RZ\Renzo\Core\Entities\Role  $role
	 * @return RZ\Renzo\Core\Entities\Role  
	 */
	private function removeRole($data, Group $group, Role $role) {
		if ($data['group_id'] == $group->getId() && 
			$data['role_id'] == $role->getId()) {
			
			if ($role !== null) {
				$group->removeRole($role);
				Kernel::getInstance()->em()->flush();	
			}
			return ($role);
		}
	}

	/**
	 * @param array $data
	 * @param RZ\Renzo\Core\Entities\Group  $group
	 * @return RZ\Renzo\Core\Entities\User
	 */
	private function addUser($data, Group $group) {
		if ($data['group_id'] == $group->getId()) {
			$user = Kernel::getInstance()->em()
				->find('RZ\Renzo\Core\Entities\User', (int)$data['user_id']);

			if ($user !== null) {
				//$group->addUser($user);
				$user->addGroup($group);
				Kernel::getInstance()->em()->flush();
				
				return ($user);
			}
		}
	}

	/**
	 * @param  array $data
	 * @param  RZ\Renzo\Core\Entities\Group  $group
	 * @param  RZ\Renzo\Core\EntitiesUser  $user
	 * @return RZ\Renzo\Core\Entities\User
	 */
	private function removeUser($data, Group $group, User $user) {
		if ($data['group_id'] == $group->getId() && 
			$data['user_id'] == $user->getId()) {
			
			if ($user !== null) {
				$user->removeGroup($group);
				Kernel::getInstance()->em()->flush();	
			}
			return ($user);
		}
	}
}