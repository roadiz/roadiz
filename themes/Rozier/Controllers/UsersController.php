<?php 
/**
 * Copyright REZO ZERO 2014
 * 
 * 
 * 
 *
 * @file UsersController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\User;
use Themes\Rozier\RozierApp;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
/**
* 
*/
class UsersController extends RozierApp
{
	/**
	 * List every users
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function indexAction( Request $request )
	{
		$users = Kernel::getInstance()->em()
			->getRepository('RZ\Renzo\Core\Entities\User')
			->findAll();
		$this->assignation['users'] = $users;

		return new Response(
			$this->getTwig()->render('users/list.html.twig', $this->assignation),
			Response::HTTP_OK,
			array('content-type' => 'text/html')
		);
	}

	/**
	 * Return an edition form for requested user
	 * @param  integer $user_id        [description]
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function editAction( Request $request, $user_id )
	{
		$user = Kernel::getInstance()->em()
			->find('RZ\Renzo\Core\Entities\User', (int)$user_id);

		if ($user !== null) {
			$this->assignation['user'] = $user;
			$form = $this->buildEditForm( $user );

			$form->handleRequest();

			if ($form->isValid()) {
		 		$this->editUser($form->getData(), $user);

		 		/*
		 		 * Force redirect to avoid resending form when refreshing page
		 		 */
		 		$response = new RedirectResponse(
					Kernel::getInstance()->getUrlGenerator()->generate(
						'usersEditPage',
						array('user_id' => $user->getId())
					)
				);
				$response->prepare($request);

				return $response->send();
			}

			$this->assignation['form'] = $form->createView();

			return new Response(
				$this->getTwig()->render('users/edit.html.twig', $this->assignation),
				Response::HTTP_OK,
				array('content-type' => 'text/html')
			);
		}
		else {
			return $this->throw404();
		}
	}

	/**
	 * Return an edition form for requested user
	 * @param  integer $user_id        [description]
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function editRolesAction( Request $request, $user_id )
	{
		$user = Kernel::getInstance()->em()
			->find('RZ\Renzo\Core\Entities\User', (int)$user_id);

		if ($user !== null) {
			$this->assignation['user'] = $user;
			
			return new Response(
				$this->getTwig()->render('users/roles.html.twig', $this->assignation),
				Response::HTTP_OK,
				array('content-type' => 'text/html')
			);
		}
		else {
			return $this->throw404();
		}
	}

	/**
	 * Return an creation form for requested user
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function addAction( Request $request )
	{
		$user = new User();

		if ($user !== null) {

			$this->assignation['user'] = $user;
			$form = $this->buildAddForm( $user );

			$form->handleRequest();

			if ($form->isValid()) {
		 		$this->addUser($form->getData(), $user);

		 		/*
		 		 * Force redirect to avoid resending form when refreshing page
		 		 */
		 		$response = new RedirectResponse(
					Kernel::getInstance()->getUrlGenerator()->generate('usersHomePage')
				);
				$response->prepare($request);

				return $response->send();
			}

			$this->assignation['form'] = $form->createView();

			return new Response(
				$this->getTwig()->render('users/add.html.twig', $this->assignation),
				Response::HTTP_OK,
				array('content-type' => 'text/html')
			);
		}
		else {
			return $this->throw404();
		}
	}

	/**
	 * Return an deletion form for requested user
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function deleteAction( Request $request, $user_id )
	{
		$user = Kernel::getInstance()->em()
			->find('RZ\Renzo\Core\Entities\User', (int)$user_id);

		if ($user !== null) {
			$this->assignation['user'] = $user;
			
			$form = $this->buildDeleteForm( $user );

			$form->handleRequest();

			if ($form->isValid() && 
				$form->getData()['user_id'] == $user->getId() ) {

		 		$this->deleteUser($form->getData(), $user);

		 		/*
		 		 * Force redirect to avoid resending form when refreshing page
		 		 */
		 		$response = new RedirectResponse(
					Kernel::getInstance()->getUrlGenerator()->generate('usersHomePage')
				);
				$response->prepare($request);

				return $response->send();
			}

			$this->assignation['form'] = $form->createView();

			return new Response(
				$this->getTwig()->render('users/delete.html.twig', $this->assignation),
				Response::HTTP_OK,
				array('content-type' => 'text/html')
			);
		}
		else {
			return $this->throw404();
		}
	}

	private function editUser( $data, User $user )
	{
		foreach ($data as $key => $value) {
			$setter = 'set'.ucwords($key);
			$user->$setter( $value );
		}

		Kernel::getInstance()->em()->flush();
		$this->getSession()->getFlashBag()->add('confirm', 'User “'.$user->getUsername().'” has been updated');
	}

	private function addUser( $data, User $user )
	{
		foreach ($data as $key => $value) {
			$setter = 'set'.ucwords($key);
			$user->$setter( $value );
		}

		Kernel::getInstance()->em()->persist($user);
		Kernel::getInstance()->em()->flush();
		$this->getSession()->getFlashBag()->add('confirm', 'User “'.$user->getUsername().'” has been created');
	}

	private function deleteUser( $data, User $user )
	{
		Kernel::getInstance()->em()->remove($user);
		Kernel::getInstance()->em()->flush();
		$this->getSession()->getFlashBag()->add('confirm', 'User “'.$user->getUsername().'” has been deleted');
	}

	/**
	 * 
	 * @param  User   $user 
	 * @return Symfony\Component\Form\Forms
	 */
	private function buildAddForm( User $user )
	{
		$defaults = array(
			'email' => $user->getEmail(),
			'username' => $user->getUsername(),
			'firstName' => $user->getFirstName(),
			'lastName' => $user->getLastName(),
			'company' => $user->getCompany(),
			'job' => $user->getJob(),
			'birthday' => $user->getBirthday(),
		);

		$builder = $this->getFormFactory()
					->createBuilder('form', $defaults)
					->add('email', 'email', array(
						'constraints' => array(
							new NotBlank()
						)
					))
					->add('username', 'text', array(
						'constraints' => array(
							new NotBlank()
						)
					))
					->add('plainPassword', 'repeated', array(
						'type' => 'password',
						'constraints' => array(
							new NotBlank()
						)
					))
					->add('firstName', 'text', array('required' => false))
					->add('lastName', 'text', array('required' => false))
					->add('company', 'text', array('required' => false))
					->add('job', 'text', array('required' => false))
					->add('birthday', 'date', array('required' => false))
		;

		return $builder->getForm();
	}
	/**
	 * 
	 * @param  User   $user 
	 * @return Symfony\Component\Form\Forms
	 */
	private function buildEditForm( User $user )
	{
		$defaults = array(
			'email' => $user->getEmail(),
			'username' => $user->getUsername(),
			'firstName' => $user->getFirstName(),
			'lastName' => $user->getLastName(),
			'company' => $user->getCompany(),
			'job' => $user->getJob(),
			'birthday' => $user->getBirthday(),
		);

		$builder = $this->getFormFactory()
					->createBuilder('form', $defaults)
					->add('email', 'email', array(
						'constraints' => array(
							new NotBlank()
						)
					))
					->add('username', 'text', array(
						'constraints' => array(
							new NotBlank()
						)
					))
					->add('plainPassword', 'repeated', array(
						'type' => 'password',
						'required' => false
					))
					->add('firstName', 'text', array('required' => false))
					->add('lastName', 'text', array('required' => false))
					->add('company', 'text', array('required' => false))
					->add('job', 'text', array('required' => false))
					->add('birthday', 'date', array('required' => false))
		;

		return $builder->getForm();
	}

	/**
	 * 
	 * @param  User   $user 
	 * @return Symfony\Component\Form\Forms
	 */
	private function buildDeleteForm( User $user )
	{
		$builder = $this->getFormFactory()
			->createBuilder('form')
			->add('user_id', 'hidden', array(
				'data' => $user->getId(),
				'constraints' => array(
					new NotBlank()
				)
			))
		;

		return $builder->getForm();
	}
}