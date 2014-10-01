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
use RZ\Renzo\Core\Events\DataInheritanceEvent;
use RZ\Renzo\Core\Services\DoctrineServiceProvider;

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

use Doctrine\ORM\Events;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;

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


    /**
     * Check if twig cache must be cleared
     *
     * @return void
     */
    public function handleTwigCache()
    {
        /*
         * No twig cache on install
         */
        try {
            $fs = new Filesystem();
            $fs->remove(array($this->getCacheDirectory()));
        } catch (IOExceptionInterface $e) {
            echo "An error occurred while deleting backend twig cache directory: ".$e->getPath();
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
                'devMode' => (boolean) $this->getService('config')['devMode'],
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
    public function indexAction(Request $request)
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
            $this->getService('urlGenerator')->generate(
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
    public function requirementsAction(Request $request)
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
     * Import nodetype screen.
     *
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
     * Install database screen.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function databaseAction(Request $request)
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

                    $config->writeConfiguration();

                    /*
                     * Force redirect to avoid resending form when refreshing page
                     */
                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'installDatabaseSchemaPage'
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
     * Perform database schema migration.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function databaseSchemaAction(Request $request)
    {
        /*
         * Test connexion
         */
        if (null === $this->getService('em')) {
            $this->assignation['error'] = true;
            $this->assignation['errorMessage'] = $c['session']->getFlashBag()->all();

        } else {

            try {
                $fixtures = new Fixtures();

                \RZ\Renzo\Console\SchemaCommand::createSchema();
                \RZ\Renzo\Console\SchemaCommand::refreshMetadata();

                $fixtures->installFixtures();

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


        return new Response(
            $this->getTwig()->render('steps/databaseDone.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * User creation screen.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function userAction(Request $request)
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
                    $user = $this->getService('em')
                                 ->getRepository('RZ\Renzo\Core\Entities\User')
                                 ->findOneBy(array('username' => $userForm->getData()['username']));

                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
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

    /**
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
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
     * User information screen.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $userId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function userSummaryAction(Request $request, $userId)
    {
        $user = $this->getService('em')->find('RZ\Renzo\Core\Entities\User', $userId);
        $this->assignation['name'] = $user->getUsername();
        $this->assignation['email'] = $user->getEmail();
        return new Response(
            $this->getTwig()->render('steps/userSummary.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * Theme install screen.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function themesAction(Request $request)
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
                            $this->getService('urlGenerator')->generate(
                                'installImportNodeTypesPage'
                            )
                        );
                        $response->prepare($request);

                        return $response->send();
                    } else {
                        $response = new RedirectResponse(
                            $this->getService('urlGenerator')->generate(
                                'installDonePage'
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
     * Install success screen.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function doneAction(Request $request)
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
                        $this->getService('urlGenerator')->generate(
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
        if (isset($conf->getConfiguration()['doctrine'])) {
            $defaults = $conf->getConfiguration()['doctrine'];
        } else {
            $defaults = array();
        }

        $builder = $this->getService('formFactory')
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
        $builder = $this->getService('formFactory')
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
     * Build form for theme and site informations.
     *
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
        $timeZone = $this->getService('config')['timezone'];

        $timeZoneList = include(dirname(__FILE__).'/Resources/import/timezones.php');


        $defaults = array(
            'site_name' => $siteName != '' ? $siteName : "My website",
            'meta_description' => $metaDescription != '' ? $metaDescription : "My website is beautiful!",
            'email_sender' => $emailSender != '' ? $emailSender : "",
            'email_sender_name' => $emailSenderName != '' ? $emailSenderName : "",
            'install_frontend' => true,
            'timezone' => $timeZone != '' ? $timeZone : "Europe/Paris"
        );
        $builder = $this->getService('formFactory')
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
                'required' => false
            ))
            ->add('timezone', 'choice', array(
                'choices' => $timeZoneList,
                'required' => true
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
        $builder = $this->getService('formFactory')
            ->createBuilder('form')
            ->add('action', 'hidden', array(
                'data' => 'quit_install'
            ));

        return $builder->getForm();
    }
}
