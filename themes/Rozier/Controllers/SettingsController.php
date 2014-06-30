<?php 
/**
 * Copyright REZO ZERO 2014
 * 
 * 
 * 
 *
 * @file SettingsController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Setting;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Entities\NodeTypeField;
use Themes\Rozier\RozierApp;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
/**
* 
*/
class SettingsController extends RozierApp
{
	/**
	 * List every settings
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function indexAction()
	{
		$settings = Kernel::getInstance()->em()
			->getRepository('RZ\Renzo\Core\Entities\Setting')
			->findAll();

		$this->assignation['settings'] = $settings;

		return new Response(
			$this->getTwig()->render('settings/list.html.twig', $this->assignation),
			Response::HTTP_OK,
			array('content-type' => 'text/html')
		);
	}

	/**
	 * Return an edition form for requested setting
	 * @param  integer $setting_id        [description]
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function editAction( $setting_id )
	{
		$setting = Kernel::getInstance()->em()
			->find('RZ\Renzo\Core\Entities\Setting', (int)$setting_id);

		if ($setting !== null) {
			$this->assignation['setting'] = $setting;
			
			$form = $this->buildEditForm( $setting );

			$form->handleRequest();

			if ($form->isValid()) {
		 		$this->editSetting($form->getData(), $setting);

		 		/*
		 		 * Force redirect to avoid resending form when refreshing page
		 		 */
		 		$response = new RedirectResponse(
					Kernel::getInstance()->getUrlGenerator()->generate(
						'settingsEditPage',
						array('setting_id' => $setting->getId())
					)
				);
				$response->prepare(Kernel::getInstance()->getRequest());

				return $response->send();
			}

			$this->assignation['form'] = $form->createView();

			return new Response(
				$this->getTwig()->render('settings/edit.html.twig', $this->assignation),
				Response::HTTP_OK,
				array('content-type' => 'text/html')
			);
		}
		else {
			return $this->throw404();
		}
	}

	/**
	 * Return an creation form for requested setting
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function addAction( )
	{
		$setting = new Setting();

		if ($setting !== null) {
			$this->assignation['setting'] = $setting;
			
			$form = $this->buildEditForm( $setting );

			$form->handleRequest();

			if ($form->isValid()) {
		 		$this->addSetting($form->getData(), $setting);

		 		/*
		 		 * Force redirect to avoid resending form when refreshing page
		 		 */
		 		$response = new RedirectResponse(
					Kernel::getInstance()->getUrlGenerator()->generate('settingsHomePage')
				);
				$response->prepare(Kernel::getInstance()->getRequest());

				return $response->send();
			}

			$this->assignation['form'] = $form->createView();

			return new Response(
				$this->getTwig()->render('settings/add.html.twig', $this->assignation),
				Response::HTTP_OK,
				array('content-type' => 'text/html')
			);
		}
		else {
			return $this->throw404();
		}
	}

	/**
	 * Return an deletion form for requested setting
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function deleteAction( $setting_id )
	{
		$setting = Kernel::getInstance()->em()
			->find('RZ\Renzo\Core\Entities\Setting', (int)$setting_id);

		if ($setting !== null) {
			$this->assignation['setting'] = $setting;
			
			$form = $this->buildDeleteForm( $setting );

			$form->handleRequest();

			if ($form->isValid() && 
				$form->getData()['setting_id'] == $setting->getId() ) {

		 		$this->deleteSetting($form->getData(), $setting);

		 		/*
		 		 * Force redirect to avoid resending form when refreshing page
		 		 */
		 		$response = new RedirectResponse(
					Kernel::getInstance()->getUrlGenerator()->generate('settingsHomePage')
				);
				$response->prepare(Kernel::getInstance()->getRequest());

				return $response->send();
			}

			$this->assignation['form'] = $form->createView();

			return new Response(
				$this->getTwig()->render('settings/delete.html.twig', $this->assignation),
				Response::HTTP_OK,
				array('content-type' => 'text/html')
			);
		}
		else {
			return $this->throw404();
		}
	}

	private function editSetting( $data, Setting $setting)
	{
		foreach ($data as $key => $value) {
			$setter = 'set'.ucwords($key);
			$setting->$setter( $value );
		}

		Kernel::getInstance()->em()->flush();
	}

	private function addSetting( $data, Setting $setting)
	{
		foreach ($data as $key => $value) {
			$setter = 'set'.ucwords($key);
			$setting->$setter( $value );
		}
		Kernel::getInstance()->em()->persist($setting);
		Kernel::getInstance()->em()->flush();
	}

	private function deleteSetting( $data, Setting $setting)
	{
		Kernel::getInstance()->em()->remove($setting);
		Kernel::getInstance()->em()->flush();
	}


	/**
	 * 
	 * @param  Setting   $setting 
	 * @return Symfony\Component\Form\Forms
	 */
	private function buildEditForm( Setting $setting )
	{
		$defaults = array(
			'name' =>           $setting->getName(),
			'value' =>    		$setting->getValue(),
			'visible' =>        $setting->isVisible(),
			'type' =>    		$setting->getType(),
		);
		$builder = $this->getFormFactory()
					->createBuilder('form', $defaults)
					->add('name', 'text', array(
						'constraints' => array(
							new NotBlank()
						)
					))
					->add('value',    		'text', array('required' => false))
					->add('visible',        'checkbox', array('required' => false))
					->add('type', 'choice', array(
						'required' => true,
						'choices' => NodeTypeField::$typeToHuman
					))
		;

		return $builder->getForm();
	}

	/**
	 * 
	 * @param  Setting   $setting 
	 * @return Symfony\Component\Form\Forms
	 */
	private function buildDeleteForm( Setting $setting )
	{
		$builder = $this->getFormFactory()
			->createBuilder('form')
			->add('setting_id', 'hidden', array(
				'data' => $setting->getId(),
				'constraints' => array(
					new NotBlank()
				)
			))
		;

		return $builder->getForm();
	}


	public static function getSettings()
	{
		return Kernel::getInstance()->em()
			->getRepository('RZ\Renzo\Core\Entities\Setting')
			->findAll();
	}
}