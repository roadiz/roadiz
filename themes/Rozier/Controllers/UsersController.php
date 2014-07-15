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
use RZ\Renzo\Core\Utils\FacebookPictureFinder;


use RZ\Renzo\Core\Exceptions\EntityAlreadyExistsException;
use RZ\Renzo\Core\Exceptions\FacebookUsernameNotFoundException;


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

				try {
		 			$this->editUser($form->getData(), $user);
		 			$msg = $this->getTranslator()->trans('user.updated', array('%name%'=>$user->getUsername()));
			 		$request->getSession()->getFlashBag()->add('confirm', $msg);
		 			$this->getLogger()->info($msg);
		 		}
				catch( FacebookUsernameNotFoundException $e){
					$request->getSession()->getFlashBag()->add('error', $e->getMessage());
			 		$this->getLogger()->warning($e->getMessage());
				}
				catch( EntityAlreadyExistsException $e ){
					$request->getSession()->getFlashBag()->add('error', $e->getMessage());
			 		$this->getLogger()->warning($e->getMessage());
				}
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

				try {
			 		$this->addUser($form->getData(), $user);
        			$user->getViewer()->sendSignInConfirmation();

			 		$msg = $this->getTranslator()->trans('user.created', array('%name%'=>$user->getUsername()));
				 	$request->getSession()->getFlashBag()->add('confirm', $msg);
			 		$this->getLogger()->info($msg);
				}
				catch( FacebookUsernameNotFoundException $e){
					$request->getSession()->getFlashBag()->add('error', $e->getMessage());
			 		$this->getLogger()->warning($e->getMessage());
				}
				catch( EntityAlreadyExistsException $e ){
					$request->getSession()->getFlashBag()->add('error', $e->getMessage());
			 		$this->getLogger()->warning($e->getMessage());
				}
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

				try {
			 		$this->deleteUser($form->getData(), $user);

			 		$msg = $this->getTranslator()->trans('user.deleted', array('%name%'=>$user->getUsername()));
				 	$request->getSession()->getFlashBag()->add('confirm', $msg);
			 		$this->getLogger()->info($msg);
				}
				catch( EntityAlreadyExistsException $e ){
					$request->getSession()->getFlashBag()->add('error', $e->getMessage());
			 		$this->getLogger()->warning($e->getMessage());
				}
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
	/**
	 * 
	 * @param  [type] $data [description]
	 * @param  User   $user [description]
	 * @return [type]       [description]
	 */
	private function editUser( $data, User $user )
	{	
		if ($data['username'] != $user->getUsername() && 
				Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\User')
				->usernameExists($data['username'])
			) {

			throw new EntityAlreadyExistsException(
				$this->getTranslator()->trans('user.cannot_update.name_already_exists', 
				array('%name%'=>$data['username'])), 1);
		}
		if ($data['email'] != $user->getEmail() && 
			Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\User')
				->emailExists($data['email'])) {

			throw new EntityAlreadyExistsException(
				$this->getTranslator()->trans('user.cannot_update.email_already_exists', 
				array('%email%'=>$data['email'])), 1);
		}

		foreach ($data as $key => $value) {
			$setter = 'set'.ucwords($key);
			$user->$setter( $value );
		}

		$this->updateProfileImage( $user );
		Kernel::getInstance()->em()->flush();
	}

	private function addUser( $data, User $user )
	{	
		if (Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\User')
				->usernameExists($data['username']) || 
			Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\User')
				->emailExists($data['email'])) {

			throw new EntityAlreadyExistsException($this->getTranslator()->trans('user.cannot_create_already_exists', array('%name%'=>$data['username'])), 1);
		}
	
		foreach ($data as $key => $value) {
			$setter = 'set'.ucwords($key);
			$user->$setter( $value );
		}

		$this->updateProfileImage( $user );
		Kernel::getInstance()->em()->persist($user);
		Kernel::getInstance()->em()->flush();
	}

	private function updateProfileImage( User $user )
	{
		if ($user->getFacebookName() != '') {
			$facebook = new FacebookPictureFinder($user->getFacebookName());
	        if (false !== $url = $facebook->getPictureUrl()) {
	            $user->setPictureUrl($url);
	        }
	        else {
	        	throw new FacebookUsernameNotFoundException(
	        		$this->getTranslator()->trans('user.facebook_name_does_not_exist', 
	        		array('%name%'=>$user->getFacebookName())), 1);
	        	
	        }
		}
	}

	private function deleteUser( $data, User $user )
	{
		Kernel::getInstance()->em()->remove($user);
		Kernel::getInstance()->em()->flush();
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
			'facebookName' => $user->getFacebookName(),
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
					->add('facebookName', 'text', array('required' => false))
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
			'facebookName' => $user->getFacebookName(),
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
					->add('facebookName', 'text', array('required' => false))
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