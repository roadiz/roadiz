<?php 


namespace Themes\Install;

use Themes\Install\Controllers\Configuration;
use Themes\Install\Controllers\Fixtures;
use Themes\Install\Controllers\Requirements;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\CMS\Controllers\FrontendController;
use RZ\Renzo\Core\Entities\Document;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Entities\User;
use RZ\Renzo\Core\Entities\Role;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\Validation;

class InstallApp extends FrontendController {

	protected static $themeName =      'Install theme';
	protected static $themeAuthor =    'Ambroise Maupate';
	protected static $themeCopyright = 'REZO ZERO';
	protected static $themeDir =       'Install';
	protected static $backendTheme = 	false;

	protected $formFactory = null;
	/**
	 * 
	 */
	protected function getFormFactory()
	{
		if ($this->formFactory === null) {

			$validator = Validation::createValidator();

			$this->formFactory = Forms::createFormFactoryBuilder()
			    ->addExtension(new ValidatorExtension($validator))
			    ->getFormFactory();
		}

		return $this->formFactory;
	}
	/**
	 * Check if twig cache must be cleared 
	 */
	public function handleTwigCache() {

		if (/*Kernel::getInstance()->isDebug()*/true) {
			try {
				$fs = new Filesystem();
				$fs->remove(array($this->getCacheDirectory()));
			} catch (IOExceptionInterface $e) {
			    echo "An error occurred while deleting backend twig cache directory: ".$e->getPath();
			}
		}
	}

	public function prepareBaseAssignation()
	{
		$this->assignation = array(
			'request' => Kernel::getInstance()->getRequest(),
			'head' => array(
				'baseUrl' => Kernel::getInstance()->getRequest()->getBaseUrl(),
				'filesUrl' => Kernel::getInstance()->getRequest()->getBaseUrl().'/'.Document::getFilesFolderName(),
				'resourcesUrl' => $this->getStaticResourcesUrl()
			)
		);

		return $this;
	}

	/**
	 * Welcome screen
	 * @param  Request $request     [description]
	 * @param  [type]  $node        [description]
	 * @param  [type]  $translation [description]
	 * @return [type]               [description]
	 */
	public function indexAction( Request $request, Node $node = NULL, Translation $translation = NULL )
	{
		return new Response(
			$this->getTwig()->render('steps/hello.html.twig', $this->assignation),
			Response::HTTP_OK,
			array('content-type' => 'text/html')
		);
	}

	/**
	 * Check requirement screen
	 * @param  Request $request     [description]
	 * @param  [type]  $node        [description]
	 * @param  [type]  $translation [description]
	 * @return [type]               [description]
	 */
	public function requirementsAction( Request $request, Node $node = NULL, Translation $translation = NULL )
	{
		$config = new Configuration();
		$config->writeConfiguration();

		$requ = new Requirements();
		$this->assignation['requirements'] = $requ->getRequirements();
		$this->assignation['totalSuccess'] = $requ->isTotalSuccess();

		return new Response(
			$this->getTwig()->render('steps/requirements.html.twig', $this->assignation),
			Response::HTTP_OK,
			array('content-type' => 'text/html')
		);
	}

	/**
	 * Install database screen
	 * @param  Request $request     [description]
	 * @param  [type]  $node        [description]
	 * @param  [type]  $translation [description]
	 * @return [type]               [description]
	 */
	public function databaseAction( Request $request, Node $node = NULL, Translation $translation = NULL )
	{	
		$config = new Configuration();
		$databaseForm = $this->buildDatabaseForm( $request, $config );

		if ($databaseForm !== null) {
			$databaseForm->handleRequest();

			if ($databaseForm->isValid()) {

				$tempConf = $config->getConfiguration();
				foreach ($databaseForm->getData() as $key => $value) {
					$tempConf['doctrine'][$key] = $value;
				}
				$config->setConfiguration($tempConf);


				$config->writeConfiguration();
				/*
				 * Test connexion
				 */
				try{
				    $fixtures = new Fixtures();
				    $fixtures->createFolders();

					Kernel::getInstance()->setupEntityManager( $config->getConfiguration() );
				    Kernel::getInstance()->em()->getConnection()->connect();

				    \RZ\Renzo\Console\SchemaCommand::createSchema();
				    \RZ\Renzo\Console\SchemaCommand::refreshMetadata();

				    $fixtures->installFixtures();
			 		/*
			 		 * Force redirect to avoid resending form when refreshing page
			 		 */
			 		$response = new RedirectResponse(
						Kernel::getInstance()->getUrlGenerator()->generate(
							'installDatabaseDonePage'
						)
					);
					$response->prepare($request);
					return $response->send();
				}
				catch( \Exception $e ){
					$this->assignation['error'] = true;
					$this->assignation['errorMessage'] = $e->getMessage() . PHP_EOL . $e->getTraceAsString();
				}
			
			}
			$this->assignation['databaseForm'] = $databaseForm->createView();
		}

		return new Response(
			$this->getTwig()->render('steps/database.html.twig', $this->assignation),
			Response::HTTP_OK,
			array('content-type' => 'text/html')
		);
	}

	/**
	 * Database success screen
	 * @param  Request $request     [description]
	 * @param  [type]  $node        [description]
	 * @param  [type]  $translation [description]
	 * @return [type]               [description]
	 */
	public function databaseDoneAction( Request $request, Node $node = NULL, Translation $translation = NULL )
	{
		return new Response(
			$this->getTwig()->render('steps/databaseDone.html.twig', $this->assignation),
			Response::HTTP_OK,
			array('content-type' => 'text/html')
		);
	}

	/**
	 * User creation screen
	 * @param  Request $request     [description]
	 * @param  [type]  $node        [description]
	 * @param  [type]  $translation [description]
	 * @return [type]               [description]
	 */
	public function userAction( Request $request, Node $node = NULL, Translation $translation = NULL )
	{
		$userForm = $this->buildUserForm( $request );

		if ($userForm !== null) {
			$userForm->handleRequest();

			if ($userForm->isValid()) {

				/*
				 * Create user
				 */
				try{
					$fixtures = new Fixtures();
					$fixtures->createDefaultUser( $userForm->getData() );
			 		/*
			 		 * Force redirect to avoid resending form when refreshing page
			 		 */
			 		$response = new RedirectResponse(
						Kernel::getInstance()->getUrlGenerator()->generate(
							'installThemesPage'
						)
					);
					$response->prepare($request);
					return $response->send();
				}
				catch( \Exception $e ){
					$this->assignation['error'] = true;
					$this->assignation['errorMessage'] = $e->getMessage() . PHP_EOL . $e->getTraceAsString();
				}
			
			}
			$this->assignation['userForm'] = $userForm->createView();
		}
		return new Response(
			$this->getTwig()->render('steps/user.html.twig', $this->assignation),
			Response::HTTP_OK,
			array('content-type' => 'text/html')
		);
	}


	/**
	 * Theme install screen
	 * @param  Request $request     [description]
	 * @param  [type]  $node        [description]
	 * @param  [type]  $translation [description]
	 * @return [type]               [description]
	 */
	public function themesAction( Request $request, Node $node = NULL, Translation $translation = NULL )
	{
		$infosForm = $this->buildInformationsForm( $request );

		if ($infosForm !== null) {
			$infosForm->handleRequest();

			if ($infosForm->isValid()) {

				/*
				 * Save informations
				 */
				try{
					$fixtures = new Fixtures();
					$fixtures->saveInformations( $infosForm->getData() );
			 		/*
			 		 * Force redirect to avoid resending form when refreshing page
			 		 */
			 		$response = new RedirectResponse(
						Kernel::getInstance()->getUrlGenerator()->generate(
							'installDonePage'
						)
					);
					$response->prepare($request);
					return $response->send();
				}
				catch( \Exception $e ){
					$this->assignation['error'] = true;
					$this->assignation['errorMessage'] = $e->getMessage() . PHP_EOL . $e->getTraceAsString();
				}
			
			}
			$this->assignation['infosForm'] = $infosForm->createView();
		}
		return new Response(
			$this->getTwig()->render('steps/themes.html.twig', $this->assignation),
			Response::HTTP_OK,
			array('content-type' => 'text/html')
		);
	}

	/**
	 * Install success screen
	 * @param  Request $request     [description]
	 * @param  [type]  $node        [description]
	 * @param  [type]  $translation [description]
	 * @return [type]               [description]
	 */
	public function doneAction( Request $request, Node $node = NULL, Translation $translation = NULL )
	{	
		
		$doneForm = $this->buildDoneForm( $request );

		if ($doneForm !== null) {
			$doneForm->handleRequest();

			if ($doneForm->isValid() && 
				$doneForm->getData()['action'] == 'quit_install') {

				/*
				 * Save informations
				 */
				try{
					$config = new Configuration();
					$configuration = $config->getConfiguration();
					$configuration['install'] = false;
					$config->setConfiguration($configuration);

					$config->writeConfiguration();


				    \RZ\Renzo\Console\SchemaCommand::refreshMetadata();

			 		/*
			 		 * Force redirect to avoid resending form when refreshing page
			 		 */
			 		$response = new RedirectResponse(
						Kernel::getInstance()->getUrlGenerator()->generate(
							'installHomePage'
						)
					);
					$response->prepare($request);
					return $response->send();
				}
				catch( \Exception $e ){
					$this->assignation['error'] = true;
					$this->assignation['errorMessage'] = $e->getMessage() . PHP_EOL . $e->getTraceAsString();
				}
			
			}
			$this->assignation['doneForm'] = $doneForm->createView();
		}

		return new Response(
			$this->getTwig()->render('steps/done.html.twig', $this->assignation),
			Response::HTTP_OK,
			array('content-type' => 'text/html')
		);
	}

	/*
	 * ============================================================================
	 * Build forms
	 * ============================================================================
	 */
	protected function buildDatabaseForm( Request $request, Configuration $conf )
	{
		$defaults = $conf->getConfiguration()['doctrine'];

		$builder = $this->getFormFactory()
					->createBuilder('form', $defaults)
					->add('driver', 'choice', array(
						'choices' => array(
							'pdo_mysql'=>'pdo_mysql',
							'pdo_pgsql'=>'pdo_pgsql',
							'oci8' => 'oci8',
							'mysqli' => 'mysqli',
						),
						'constraints' => array(
							new NotBlank()
						)
					))
					->add('host', 'text', array(
						'attr'=>array(
							"autocomplete"=>"off"
						),
						'constraints' => array(
							new NotBlank()
						)
					))
					->add('user', 'text', array(
						'attr'=>array(
							"autocomplete"=>"off"
						),
						'constraints' => array(
							new NotBlank()
						)
					))
					->add('password', 'password', array(
						'attr'=>array(
							"autocomplete"=>"off"
						),
						'constraints' => array(
							new NotBlank()
						)
					))
					->add('dbname', 'text', array(
						'attr'=>array(
							"autocomplete"=>"off"
						),
						'constraints' => array(
							new NotBlank()
						)
					))
		;

		return $builder->getForm();
	}


	/*
	 * ============================================================================
	 * Build forms
	 * ============================================================================
	 */
	protected function buildUserForm( Request $request )
	{
	
		$builder = $this->getFormFactory()
					->createBuilder('form')
					->add('username', 'text', array(
						'required' => true,
						'constraints' => array(
							new NotBlank()
						)
					))
					->add('email', 'email', array(
						'required' => true,
						'constraints' => array(
							new NotBlank()
						)
					))
					->add('password', 'repeated', array(
						'invalid_message' => 'Passwords must match',
						'first_options'  => array('label' => 'Password'),
    					'second_options' => array('label' => 'Password (verify)'),
    					'required' => true,
						'constraints' => array(
							new NotBlank()
						)
					))
		;

		return $builder->getForm();
	}

	/*
	 * ============================================================================
	 * Build forms
	 * ============================================================================
	 */
	protected function buildInformationsForm( Request $request )
	{
		$siteName = \RZ\Renzo\Core\Bags\SettingsBag::get('site_name');
		$metaDescription = \RZ\Renzo\Core\Bags\SettingsBag::get('meta_description');
		$emailSender = \RZ\Renzo\Core\Bags\SettingsBag::get('email_sender');
		$emailSenderName = \RZ\Renzo\Core\Bags\SettingsBag::get('email_sender_name');

		$defaults = array(
			'site_name' => $siteName != '' ? $siteName : "My website",
			'meta_description' => $metaDescription != '' ? $metaDescription : "My website is beautiful!",
			'email_sender' => $emailSender != '' ? $emailSender : "",
			'email_sender_name' => $emailSenderName != '' ? $emailSenderName : "",
			'install_frontend' => true
		);
		$builder = $this->getFormFactory()
					->createBuilder('form', $defaults)
					->add('site_name', 'text', array(
						'required' => true,
						'constraints' => array(
							new NotBlank()
						)
					))
					->add('email_sender', 'email', array(
						'required' => true,
						'constraints' => array(
							new NotBlank()
						)
					))
					->add('email_sender_name', 'text', array(
						'required' => true,
						'constraints' => array(
							new NotBlank()
						)
					))
					->add('meta_description', 'text', array(
						'required' => false
					))
					->add('install_frontend', 'checkbox', array(
						'label' => 'Install the default front-end theme?',
						'required' => false
					))
		;

		return $builder->getForm();
	}

	protected function buildDoneForm( Request $request )
	{
		$builder = $this->getFormFactory()
					->createBuilder('form')
					->add('action', 'hidden', array(
						'data' => 'quit_install'
					))
		;
		
		return $builder->getForm();
	}
}