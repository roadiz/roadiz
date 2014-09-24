<?php
/*
 * Copyright REZO ZERO 2014
 *
 * Description
 *
 * @file InstallApp.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */

namespace Themes\Install;

use Themes\Install\Controllers\Configuration;
use Themes\Install\Controllers\Fixtures;
use Themes\Install\Controllers\Requirements;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\CMS\Controllers\AppController;
use RZ\Renzo\Core\Entities\Document;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Entities\User;
use RZ\Renzo\Core\Entities\Role;

use Symfony\Component\Finder\Finder;
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

/**
 * Installation application
 */
class InstallApp extends AppController
{

    protected static $themeName =      'Install theme';
    protected static $themeAuthor =    'Ambroise Maupate';
    protected static $themeCopyright = 'REZO ZERO';
    protected static $themeDir =       'Install';
    protected static $backendTheme =    false;

    protected $formFactory = null;


    /**
     * Remove trailing slash.
     * 
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function removeTrailingSlashAction(Request $request)
    {

        $pathInfo = $request->getPathInfo();
        $requestUri = $request->getRequestUri();

        $url = str_replace($pathInfo, rtrim($pathInfo, ' /'), $requestUri);

        $response = new RedirectResponse($url);

        $response->prepare($request);

        return $response->send();

        // return $request->redirect($url, 301);
    }


    /**
     * @return Symfony\Component\Form\Forms $formFactory
     */
    protected function getFormFactory()
    {
        if (null === $this->formFactory) {

            $validator = Validation::createValidator();

            $this->formFactory = Forms::createFormFactoryBuilder()
                ->addExtension(new ValidatorExtension($validator))
                ->getFormFactory();
        }

        return $this->formFactory;
    }


    /**
     * Check if twig cache must be cleared
     *
     * @return void
     */
    public function handleTwigCache()
    {

        if (/*$this->getKernel()->isDebug()*/true) {
            try {
                $fs = new Filesystem();
                $fs->remove(array($this->getCacheDirectory()));
            } catch (IOExceptionInterface $e) {
                echo "An error occurred while deleting backend twig cache directory: ".$e->getPath();
            }
        }
    }

    /**
     * @return array $assignation
     */
    public function prepareBaseAssignation()
    {
        $this->assignation = array(
            'request' => $this->getKernel()->getRequest(),
            'head' => array(
                'ajax' => $this->getKernel()->getRequest()->isXmlHttpRequest(),
                'cmsVersion' => Kernel::CMS_VERSION,
                'cmsBuild' => Kernel::$cmsBuild,
                'devMode' => (boolean) $this->getKernel()->getConfig()['devMode'],
                'baseUrl' => $this->getKernel()->getRequest()->getBaseUrl(),
                'filesUrl' => $this->getKernel()
                                   ->getRequest()
                                   ->getBaseUrl().'/'.Document::getFilesFolderName(),
                'resourcesUrl' => $this->getStaticResourcesUrl(),
                'grunt' => include(dirname(__FILE__).'/static/public/config/assets.config.php')
            ),
        );

        return $this;
    }

    /**
     * Welcome screen.
     * 
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param RZ\Renzo\Core\Entities\Node              $node
     * @param RZ\Renzo\Core\Entities\Translation       $translation
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request, Node $node = null, Translation $translation = null)
    {
        return new Response(
            $this->getTwig()->render('steps/hello.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * Welcome screen redirect.
     * 
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function redirectIndexAction(Request $request)
    {
        $response = new RedirectResponse(
            $this->getKernel()->getUrlGenerator()->generate(
                'installHomePage'
            )
        );

        $response->prepare($request);

        return $response->send();
    }


    /**
     * Check requirement screen.
     * 
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param RZ\Renzo\Core\Entities\Node              $node
     * @param RZ\Renzo\Core\Entities\Translation       $translation
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function requirementsAction(Request $request, Node $node = null, Translation $translation = null)
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
     * Import screen
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function importAction(Request $request)
    {
        $this->assignation['names'] = array("installImportSettings", "installImportRoles", "installImportGroups");
        return new Response(
            $this->getTwig()->render('steps/import.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * Import nodetype screen
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function nodeTypesAction(Request $request)
    {
        $finder = new Finder();

        // Extracting the PHP files from every Theme folder
        $iterator = $finder
            ->files()
            ->name('*.rzt')
            ->depth(0)
            ->in(RENZO_ROOT.'/themes/Install/Resources/import/nodetype');
        foreach ($iterator as $file) {
            $this->assignation['names'][] = str_replace(".rzt", '', $file->getFileName());
        }
        return new Response(
            $this->getTwig()->render('steps/importNodeType.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * Install database screen
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param RZ\Renzo\Core\Entities\Node              $node
     * @param RZ\Renzo\Core\Entities\Translation       $translation
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function databaseAction(Request $request, Node $node = null, Translation $translation = null)
    {
        $config = new Configuration();
        $databaseForm = $this->buildDatabaseForm($request, $config);

        if ($databaseForm !== null) {
            $databaseForm->handleRequest();

            if ($databaseForm->isValid()) {

                $tempConf = $config->getConfiguration();
                foreach ($databaseForm->getData() as $key => $value) {
                    $tempConf['doctrine'][$key] = $value;
                }
                $config->setConfiguration($tempConf);


                /*
                 * Test connexion
                 */
                try {
                    $fixtures = new Fixtures();
                    $fixtures->createFolders();

                    $this->getKernel()->setupEntityManager($config->getConfiguration());
                    $this->getKernel()->em()->getConnection()->connect();

                    \RZ\Renzo\Console\SchemaCommand::createSchema();
                    \RZ\Renzo\Console\SchemaCommand::refreshMetadata();

                    $fixtures->installFixtures();

                    $config->writeConfiguration();

                    /*
                     * Force redirect to avoid resending form when refreshing page
                     */
                    $response = new RedirectResponse(
                        $this->getKernel()->getUrlGenerator()->generate(
                            'installDatabaseDonePage'
                        )
                    );
                    $response->prepare($request);

                    return $response->send();
                } catch (\PDOException $e) {
                    $message = "";
                    if (strstr($e->getMessage(), 'SQLSTATE[')) {
                        preg_match('/SQLSTATE\[(\w+)\] \[(\w+)\] (.*)/', $e->getMessage(), $matches);
                        $message = $matches[3];
                    } else {
                        $message = $e->getMessage();
                    }
                    $this->assignation['error'] = true;
                    $this->assignation['errorMessage'] = ucfirst($message);
                } catch (\Exception $e) {
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
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param RZ\Renzo\Core\Entities\Node              $node
     * @param RZ\Renzo\Core\Entities\Translation       $translation
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function databaseDoneAction(Request $request, Node $node = null, Translation $translation = null)
    {
        return new Response(
            $this->getTwig()->render('steps/databaseDone.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * User creation screen
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param RZ\Renzo\Core\Entities\Node              $node
     * @param RZ\Renzo\Core\Entities\Translation       $translation
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function userAction(Request $request, Node $node = null, Translation $translation = null)
    {
        $userForm = $this->buildUserForm($request);

        if ($userForm !== null) {
            $userForm->handleRequest();

            if ($userForm->isValid()) {
                /*
                 * Create user
                 */
                try {
                    $fixtures = new Fixtures();
                    $fixtures->createDefaultUser($userForm->getData());
                    /*
                     * Force redirect to avoid resending form when refreshing page
                     */
                    $user = Kernel::getInstance()->em()->getRepository('RZ\Renzo\Core\Entities\User')->findOneBy(array('username' => $userForm->getData()['username']));
                    $response = new RedirectResponse(
                        $this->getKernel()->getUrlGenerator()->generate(
                            'installUserSummaryPage',
                            array("userId" => $user->getId())
                        )
                    );
                    $response->prepare($request);

                    return $response->send();
                } catch (\Exception $e) {
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

    public function updateSchemaAction(Request $request)
    {
        \RZ\Renzo\Console\SchemaCommand::updateSchema();
        return new Response(
            json_encode(array('status' => true)),
            Response::HTTP_OK,
            array('content-type' => 'application/javascript')
        );
    }

    /**
     * User information screen
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $userId
     * @param RZ\Renzo\Core\Entities\Node              $node
     * @param RZ\Renzo\Core\Entities\Translation       $translation
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function userSummaryAction(Request $request, $userId, Node $node = null, Translation $translation = null)
    {
        $user = Kernel::getInstance()->em()->find('RZ\Renzo\Core\Entities\User', $userId);
        $this->assignation['name'] = $user->getUsername();
        $this->assignation['email'] = $user->getEmail();
        return new Response(
            $this->getTwig()->render('steps/userSummary.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * Theme install screen
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param RZ\Renzo\Core\Entities\Node              $node
     * @param RZ\Renzo\Core\Entities\Translation       $translation
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function themesAction(Request $request, Node $node = null, Translation $translation = null)
    {
        $infosForm = $this->buildInformationsForm($request);

        if ($infosForm !== null) {
            $infosForm->handleRequest();

            if ($infosForm->isValid()) {

                /*
                 * Save informations
                 */
                try {
                    $fixtures = new Fixtures();
                    $fixtures->saveInformations($infosForm->getData());

                    if ($infosForm->getData()['install_frontend'] === true) {
                        /*
                         * Force redirect to avoid resending form when refreshing page
                         */
                        $response = new RedirectResponse(
                            $this->getKernel()->getUrlGenerator()->generate(
                                'installImportNodeTypesPage'
                            )
                        );
                        $response->prepare($request);

                        return $response->send();
                    }

                } catch (\Exception $e) {
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
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param RZ\Renzo\Core\Entities\Node              $node
     * @param RZ\Renzo\Core\Entities\Translation       $translation
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function doneAction(Request $request, Node $node = null, Translation $translation = null)
    {
        $doneForm = $this->buildDoneForm($request);

        if ($doneForm !== null) {
            $doneForm->handleRequest();

            if ($doneForm->isValid() &&
                $doneForm->getData()['action'] == 'quit_install') {

                /*
                 * Save informations
                 */
                try {
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
                        $this->getKernel()->getUrlGenerator()->generate(
                            'installHomePage'
                        )
                    );
                    $response->prepare($request);

                    return $response->send();
                } catch (\Exception $e) {
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

    /**
     * Build forms
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param Themes\Install\Controllers\Configuration $conf
     *
     * @return Symfony\Component\Form\Forms
     */
    protected function buildDatabaseForm(Request $request, Configuration $conf)
    {
        $defaults = $conf->getConfiguration()['doctrine'];
        $builder = $this->getFormFactory()
            ->createBuilder('form', $defaults)
            ->add('driver', 'choice', array(
                'choices' => array(
                    'pdo_mysql'=>'pdo_mysql',
                    'pdo_pgsql'=>'pdo_pgsql',
                    'pdo_sqlite' => 'pdo_sqlite',
                    'oci8' => 'oci8',
                ),
                'constraints' => array(
                    new NotBlank()
                ),
                'attr' => array(
                    "id" => "choice"
                )
            ))
            ->add('host', 'text', array(
                "required"=>false,
                'attr'=>array(
                    "autocomplete"=>"off",
                    'id' => "host"
                )
            ))
            ->add('port', 'integer', array(
                "required"=>false,
                'attr'=>array(
                    "autocomplete"=>"off",
                    'id' => "port"
                )
            ))
            ->add('unix_socket', 'text', array(
                "required"=>false,
                'attr'=>array(
                    "autocomplete"=>"off",
                    'id' => "unix_socket"
                )
            ))
            ->add('path', 'text', array(
                "required"=>false,
                'attr'=>array(
                    "autocomplete"=>"off",
                    'id' => "path"
                )
            ))
            ->add('user', 'text', array(
                'attr'=>array(
                    "autocomplete"=>"off",
                    'id' => "user"
                ),
                'constraints' => array(
                    new NotBlank()
                )
            ))
            ->add('password', 'password', array(
                "required"=>false,
                'attr'=>array(
                    "autocomplete"=>"off",
                    'id'=>'password'
                )
            ))
            ->add('dbname', 'text', array(
                "required"=>false,
                'attr'=>array(
                    "autocomplete"=>"off",
                    'id'=>'dbname'
                )
            ));

        return $builder->getForm();
    }

    /**
     * Build forms
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\Form\Forms
     */
    protected function buildUserForm(Request $request)
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
                'type' => 'password',
                'invalid_message' => 'password.must_match',
                'first_options'  => array('label' => 'password'),
                'second_options' => array('label' => 'password.verify'),
                'required' => true,
                'constraints' => array(
                    new NotBlank()
                )
            ));

        return $builder->getForm();
    }

    /**
     * Build forms
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\Form\Forms
     */
    protected function buildInformationsForm(Request $request)
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
            ));

        return $builder->getForm();
    }

    /**
     * Build forms
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\Form\Forms
     */
    protected function buildDoneForm(Request $request)
    {
        $builder = $this->getFormFactory()
            ->createBuilder('form')
            ->add('action', 'hidden', array(
                'data' => 'quit_install'
            ));

        return $builder->getForm();
    }
}
