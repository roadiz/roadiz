<?php
/*
 * Copyright REZO ZERO 2014
 *
 * Description
 *
 * @file Kernel.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core;

use RZ\Renzo\Core\Events\DataInheritanceEvent;
use RZ\Renzo\Core\Routing\MixedUrlMatcher;
use RZ\Renzo\Core\Bags\SettingsBag;
use RZ\Renzo\Core\Handlers\UserProvider;
use RZ\Renzo\Core\Handlers\UserHandler;
use RZ\Renzo\Core\Entities\Theme;
use RZ\Renzo\Core\Authentification\AuthenticationSuccessHandler;
use RZ\Renzo\Core\Authorization\AccessDeniedHandler;

use Doctrine\ORM\Events;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;

use Symfony\Component\Console\Application;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\Csrf\CsrfProvider\SessionCsrfProvider;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Routing;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\Matcher\Dumper\PhpMatcherDumper;
use Symfony\Component\Routing\Generator\Dumper\PhpGeneratorDumper;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserChecker;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\Authorization\Voter\RoleVoter;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Firewall;
use Symfony\Component\Security\Http\FirewallMap;
use Symfony\Component\Security\Http\Firewall\ExceptionListener;
use Symfony\Component\Security\Http\Firewall\ContextListener;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver;
use Pimple\Container;
use Solarium\Client;

/**
 * Main renzo CMS entry point.
 */
class Kernel
{
    const CMS_VERSION =         'alpha';
    const SECURITY_DOMAIN =     'rzcms_domain';
    const INSTALL_CLASSNAME =   'Themes\\Install\\InstallApp';

    public static $cmsBuild =   null;

    private static $instance =  null;
    public $container =         null;
    private $backendDebug =     false;

    protected $request =        null;
    protected $response =       null;

    /**
     * Kernel constructor.
     */
    final private function __construct()
    {
        $this->container = new Container();
        /*
         * Get build number from txt file generated at each pre-commit
         */
        if (file_exists(RENZO_ROOT.'/BUILD.txt')) {
            static::$cmsBuild = intval(trim(file_get_contents(RENZO_ROOT.'/BUILD.txt')));
        }
        $this->container['stopwatch'] = function ($c) {
            return new Stopwatch();
        };

        $this->container['stopwatch']->start('global');
        $this->container['stopwatch']->start('initKernel');

        $this->request = Request::createFromGlobals();

        $this->container['stopwatch']->stop('initKernel');

        $this->setupDependencyInjection();

        if ($this->isDebug() ||
            !file_exists(RENZO_ROOT.'/sources/Compiled/GlobalUrlMatcher.php') ||
            !file_exists(RENZO_ROOT.'/sources/Compiled/GlobalUrlGenerator.php')) {
            $this->dumpUrlUtils();
        }
    }

    /**
     * Get Pimple dependency injection service container.
     *
     * @param string $key Service name
     *
     * @return mixed
     */
    public static function getService($key)
    {
        return static::getInstance()->container[$key];
    }

    protected function setupDependencyInjection()
    {
        /*
         * Inject app config
         */
        $this->container['config'] = function ($c) {
            $configFile = RENZO_ROOT.'/conf/config.json';
            if (file_exists($configFile)) {
                return json_decode(file_get_contents($configFile), true);
            } else {
                return null;
            }
        };

        $this->container['dispatcher'] = function ($c) {
            return new EventDispatcher();
        };
        $this->container['resolver'] = function ($c) {
            return new ControllerResolver();
        };
        $this->container['httpKernel'] = function ($c) {
            return new HttpKernel($c['dispatcher'], $c['resolver']);
        };

        $this->setupEntitiesPaths();
        $this->setupEntityManager();
        $this->setupSolrService();
        $this->setupSecurityContext();
        $this->setupRouteCollection();

        $this->container['backendClass'] = function ($c) {
            $theme = $c['em']
                          ->getRepository('RZ\Renzo\Core\Entities\Theme')
                          ->findOneBy(array('available'=>true, 'backendTheme'=>true));

            if ($theme !== null) {
                return $theme->getClassName();
            }

            return 'RZ\Renzo\CMS\Controllers\BackendController';
        };

        $this->container['frontendThemes'] = function ($c) {
            $themes = $c['em']
                          ->getRepository('RZ\Renzo\Core\Entities\Theme')
                          ->findBy(array(
                              'available'=>    true,
                              'backendTheme'=> false
                          ));



            if (count($themes) < 1) {

                $defaultTheme = new Theme();
                $defaultTheme->setClassName('RZ\Renzo\CMS\Controllers\FrontendController');
                $defaultTheme->setAvailable(true);

                return array(
                    $defaultTheme
                );
            } else {
                return $themes;
            }
        };
        $this->container['requestContext'] = function ($c) {
            $rc = new Routing\RequestContext(Kernel::getInstance()->getResolvedBaseUrl());
            $rc->setHost(Kernel::getInstance()->getRequest()->server->get('HTTP_HOST'));
            $rc->setHttpPort(intval(Kernel::getInstance()->getRequest()->server->get('SERVER_PORT')));

            return $rc;
        };
        $this->container['urlMatcher'] = function ($c) {
            return new MixedUrlMatcher($c['requestContext']);
        };
        $this->container['urlGenerator'] = function ($c) {
            return new \GlobalUrlGenerator($c['requestContext']);
        };
        $this->container['httpUtils'] = function ($c) {
            return new HttpUtils($c['urlGenerator'], $c['urlMatcher']);
        };

        $this->container['formValidator'] = function ($c) {
            return Validation::createValidator();
        };
        $this->container['formFactory'] = function ($c) {
            return Forms::createFormFactoryBuilder()
                ->addExtension(new CsrfExtension($c['csrfProvider']))
                ->addExtension(new ValidatorExtension($c['formValidator']))
                ->getFormFactory();
        };

        $this->container['logger'] = function ($c) {
            $logger = new \RZ\Renzo\Core\Log\Logger();
            $logger->setSecurityContext($c['securityContext']);

            return $logger;
        };

        return $this;

    }

    /**
     * Setup Solr service in DI container.
     */
    protected function setupSolrService()
    {
        $this->container['solr'] = function ($c) {

            if ($this->isSolrAvailable()) {
                if (null === $this->solrService) {
                    $this->solrService = new \Solarium\Client($c['config']['solr']);
                    $this->solrService->setDefaultEndpoint('localhost');
                }

                return $this->solrService;
            }

            return null;
        };
    }

    /**
     * Setup entities search paths in DI container.
     */
    protected function setupEntitiesPaths()
    {
        $this->container['entitiesPaths'] = array(
            "src/Renzo/Core/Entities",
            "src/Renzo/Core/AbstractEntities",
            "sources/GeneratedNodeSources"
        );
    }

    protected function setupRouteCollection()
    {
        if (!$this->isInstallMode()) {
            $this->container['routeCollection'] = function ($c) {
                $rCollection = new RouteCollection();

                /*
                 * Add Assets controller routes
                 */
                $rCollection->addCollection(\RZ\Renzo\CMS\Controllers\AssetsController::getRoutes());

                /*
                 * Add Backend routes
                 */
                $beClass = $c['backendClass'];
                $cmsCollection = $beClass::getRoutes();
                if ($cmsCollection !== null) {
                    $rCollection->addCollection(
                        $cmsCollection,
                        '/rz-admin',
                        array('_scheme' => 'https')
                    );
                }

                /*
                 * Add Frontend routes
                 *
                 * return 'RZ\Renzo\CMS\Controllers\FrontendController';
                 */
                foreach ($c['frontendThemes'] as $theme) {
                    $feClass = $theme->getClassName();
                    $feCollection = $feClass::getRoutes();
                    if ($feCollection !== null) {

                        // set host pattern if defined
                        if ($theme->getHostname() != '*' &&
                            $theme->getHostname() != '') {

                            $feCollection->setHost($theme->getHostname());
                        }
                        $rCollection->addCollection($feCollection);
                    }
                }

                return $rCollection;
            };
        } else {
            $this->container['routeCollection'] = function ($c) {

                $installClassname = static::INSTALL_CLASSNAME;
                $feCollection = $installClassname::getRoutes();
                $rCollection = new RouteCollection();
                $rCollection->addCollection($feCollection);

                return $rCollection;
            };
        }
    }
    private function setupSecurityContext()
    {
        $this->container['session'] = function ($c) {
            $session = new Session();
            Kernel::getInstance()->getRequest()->setSession($session);
            return $session;
        };

        $this->container['csrfProvider'] = function ($c) {
            $csrfSecret = $c['config']["security"]['secret'];
            return new SessionCsrfProvider(
                $c['session'],
                $csrfSecret
            );
        };

        $this->container['contextListener'] = function ($c) {

            $c['session']; //Force session handler

            return new ContextListener(
                $c['securityContext'],
                array($c['userProvider']),
                static::SECURITY_DOMAIN,
                $c['logger'],
                $c['dispatcher']
            );
        };

        $this->container['userProvider'] = function ($c) {
            return new UserProvider();
        };
        $this->container['userChecker'] = function ($c) {
            return new UserChecker();
        };
        $this->container['authentificationManager'] = function ($c) {
            return new DaoAuthenticationProvider(
                $c['userProvider'],
                $c['userChecker'],
                static::SECURITY_DOMAIN,
                UserHandler::getEncoderFactory()
            );
        };
        $this->container['accessDecisionManager'] = function ($c) {
            return new AccessDecisionManager(
                array(
                    new RoleVoter('ROLE_')
                )
            );
        };
        $this->container['securityContext'] = function ($c) {
            return new SecurityContext(
                $c['authentificationManager'],
                $c['accessDecisionManager']
            );
        };

        $this->container['firewallMap'] = function ($c) {
            return new FirewallMap();
        };


        $this->container['firewallExceptionListener'] = function ($c) {

            return new \Symfony\Component\Security\Http\Firewall\ExceptionListener(
                $c['securityContext'],
                new \Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver('', ''),
                $c['httpUtils'],
                Kernel::SECURITY_DOMAIN,
                new \Symfony\Component\Security\Http\EntryPoint\FormAuthenticationEntryPoint(
                    $c['httpKernel'],
                    $c['httpUtils'],
                    '/login',
                    true // bool $useForward
                ),
                null, //$errorPage
                $c['accessDeniedHandler'],
                $c['logger'] //LoggerInterface $logger
            );
        };

        /*
         * Default denied handler
         */
        $this->container['accessDeniedHandler'] = function ($c) {
            return new \RZ\Renzo\Core\Authorization\AccessDeniedHandler();
        };
    }

    /**
     * Initialize Doctrine entity manager in DI container.
     *
     * This method can be called from InstallApp after updating
     * doctrine configuration.
     */
    public function setupEntityManager()
    {
        if ($this->container['config'] !== null &&
            isset($this->container['config']["doctrine"])) {

            $this->container['em'] = function ($c) {

                // the connection configuration
                $dbParams = $c['config']["doctrine"];
                $configDB = Setup::createAnnotationMetadataConfiguration(
                    $c['entitiesPaths'],
                    $this->isDebug()
                );

                $configDB->setProxyDir(RENZO_ROOT . '/sources/Proxies');
                $configDB->setProxyNamespace('Proxies');

                $em = EntityManager::create($dbParams, $configDB);

                $evm = $em->getEventManager();

                /*
                 * Create dynamic dicriminator map for our Node system
                 */
                $inheritableEntityEvent = new DataInheritanceEvent();
                $evm->addEventListener(Events::loadClassMetadata, $inheritableEntityEvent);

                if ($em->getConfiguration()->getResultCacheImpl() !== null) {
                    $em->getConfiguration()
                            ->getResultCacheImpl()
                            ->setNamespace($c['config']["appNamespace"]);
                }
                if ($em->getConfiguration()->getHydrationCacheImpl() !== null) {
                    $em->getConfiguration()
                            ->getHydrationCacheImpl()
                            ->setNamespace($c['config']["appNamespace"]);
                }
                if ($em->getConfiguration()->getQueryCacheImpl() !== null) {
                    $em->getConfiguration()
                            ->getQueryCacheImpl()
                            ->setNamespace($c['config']["appNamespace"]);
                }
                if ($em->getConfiguration()->getMetadataCacheImpl()) {
                    $em->getConfiguration()
                            ->getMetadataCacheImpl()
                            ->setNamespace($c['config']["appNamespace"]);
                }

                return $em;
            };
        }
    }

    /**
     * @return RZ\Renzo\Core\Kernel $this
     */
    public function runConsole()
    {
        $this->backendDebug = (boolean) SettingsBag::get('backend_debug');

        /*
         * Define a request wide timezone
         */
        if (!empty($this->container['config']["timezone"])) {
            date_default_timezone_set($this->container['config']["timezone"]);
        } else {
            date_default_timezone_set("Europe/Paris");
        }

        if ($this->isInstallMode()) {
            $this->prepareSetup();
        }

        $application = new Application('Renzo Console Application', '0.1');
        $application->add(new \RZ\Renzo\Console\TranslationsCommand);
        $application->add(new \RZ\Renzo\Console\NodeTypesCommand);
        $application->add(new \RZ\Renzo\Console\NodesCommand);
        $application->add(new \RZ\Renzo\Console\SchemaCommand);
        $application->add(new \RZ\Renzo\Console\ThemesCommand);
        $application->add(new \RZ\Renzo\Console\InstallCommand);
        $application->add(new \RZ\Renzo\Console\UsersCommand);
        $application->add(new \RZ\Renzo\Console\RequirementsCommand);
        $application->add(new \RZ\Renzo\Console\SolrCommand);

        $application->run();

        $this->container['stopwatch']->stop('global');

        return $this;
    }

    /**
     * @return boolean
     */
    public function isInstallMode()
    {
        if ($this->container['config'] === null ||
            (isset($this->container['config']['install']) &&
             $this->container['config']['install'] == true)) {

            return true;
        } else {
            return false;
        }
    }

    /**
     * Run main HTTP application.
     *
     * @return RZ\Renzo\Core\Kernel $this
     */
    public function runApp()
    {

        /*
         * Define a request wide timezone
         */
        if (!empty($this->container['config']["timezone"])) {
            date_default_timezone_set($this->container['config']["timezone"]);
        } else {
            date_default_timezone_set("Europe/Paris");
        }



        if ($this->container['config'] === null ||
            (isset($this->container['config']['install']) &&
             $this->container['config']['install'] == true)) {

            $this->prepareSetup();

        } else {
            $this->debug = (boolean) SettingsBag::get('debug');
            $this->backendDebug = (boolean) SettingsBag::get('backend_debug');
            $this->prepareRequestHandling();
        }

        try {
            /*
             * ----------------------------
             * Main Framework handle call
             * ----------------------------
             */
            $this->response = $this->container['httpKernel']->handle($this->request);
            $this->response->send();
            $this->container['httpKernel']->terminate($this->request, $this->response);

        } catch (\Symfony\Component\Routing\Exception\ResourceNotFoundException $e) {
            echo $e->getMessage().PHP_EOL;
        }

        return $this;
    }

    /**
     * Run first installation interface.
     *
     * @return RZ\Renzo\Core\Kernel $this
     */
    public function prepareSetup()
    {
        $this->container['dispatcher']->addSubscriber(new RouterListener($this->container['urlMatcher']));

        $this->container['dispatcher']->addListener(
            KernelEvents::CONTROLLER,
            array(
                new \RZ\Renzo\Core\Events\ControllerMatchedEvent($this),
                'onControllerMatched'
            )
        );

        return $this;
    }

    /**
     * Save a compiled version of UrlMatcher and UrlGenerator.
     */
    protected function dumpUrlUtils()
    {
        $this->container['stopwatch']->start('prepareRouting');
        if (!file_exists(RENZO_ROOT.'/sources/Compiled')) {
            mkdir(RENZO_ROOT.'/sources/Compiled', 0755, true);
        }

        /*
         * Generate custom UrlMatcher
         */
        $dumper = new PhpMatcherDumper($this->container['routeCollection']);
        $class = $dumper->dump(array(
            'class' => 'GlobalUrlMatcher'
        ));
        file_put_contents(RENZO_ROOT.'/sources/Compiled/GlobalUrlMatcher.php', $class);

        /*
         * Generate custom UrlGenerator
         */
        $dumper = new PhpGeneratorDumper($this->container['routeCollection']);
        $class = $dumper->dump(array(
            'class' => 'GlobalUrlGenerator'
        ));
        file_put_contents(RENZO_ROOT.'/sources/Compiled/GlobalUrlGenerator.php', $class);

        $this->container['stopwatch']->stop('prepareRouting');
    }

    /**
     * Prepare Translation generation tools.
     */
    private function prepareTranslation()
    {
        /*
         * set default locale
         */
        $translation = $this->container['em']
                            ->getRepository('RZ\Renzo\Core\Entities\Translation')
                            ->findDefault();

        if ($translation !== null) {
            $shortLocale = $translation->getShortLocale();
            $this->request->setLocale($shortLocale);
            \Locale::setDefault($shortLocale);
        }
    }

    /**
     * Prepare backend and frontend routes and logic.
     *
     * @return boolean
     */
    private function prepareRequestHandling()
    {
        $this->container['stopwatch']->start('prepareTranslation');
        $this->prepareTranslation();
        $this->container['stopwatch']->stop('prepareTranslation');

        $this->container['dispatcher']->addSubscriber(new RouterListener($this->container['urlMatcher']));
        /*
         * Security
         */
        $this->container['stopwatch']->start('firewall');

        // Register back-end security scheme
        $beClass = $this->container['backendClass'];
        $beClass::setupDependencyInjection($this->container);

        // Register front-end security scheme
        foreach ($this->container['frontendThemes'] as $theme) {
            $feClass = $theme->getClassName();
            $feClass::setupDependencyInjection($this->container);
        }

        $firewall = new Firewall($this->container['firewallMap'], $this->container['dispatcher']);
        $this->container['stopwatch']->stop('firewall');

        /*
         * Events
         */
        $this->container['dispatcher']->addListener(
            KernelEvents::REQUEST,
            array(
                $this,
                'onStartKernelRequest'
            )
        );
        $this->container['dispatcher']->addListener(
            KernelEvents::REQUEST,
            array(
                $firewall,
                'onKernelRequest'
            )
        );
        /*
         * Register after controller matched listener
         */
        $this->container['dispatcher']->addListener(
            KernelEvents::CONTROLLER,
            array(
                $this,
                'onControllerMatched'
            )
        );
        $this->container['dispatcher']->addListener(
            KernelEvents::CONTROLLER,
            array(
                new \RZ\Renzo\Core\Events\ControllerMatchedEvent($this),
                'onControllerMatched'
            )
        );
        $this->container['dispatcher']->addListener(
            KernelEvents::TERMINATE,
            array(
                $this,
                'onKernelTerminate'
            )
        );
        /*
         * If debug, alter HTML responses to append Debug panel to view
         */
        if (true == SettingsBag::get('display_debug_panel')) {
            $this->container['dispatcher']->addSubscriber(new \RZ\Renzo\Core\Utils\DebugPanel());
        }
    }
    /**
     * Start a stopwatch event when a kernel start handling.
     */
    public function onStartKernelRequest()
    {
        $this->container['stopwatch']->start('requestHandling');
    }
    /**
     * Stop request-handling stopwatch event and
     * start a new stopwatch event when a controller is instanciated.
     */
    public function onControllerMatched()
    {
        $this->container['stopwatch']->stop('matchingRoute');
        $this->container['stopwatch']->stop('requestHandling');
        $this->container['stopwatch']->start('controllerHandling');
    }
    /**
     * Stop controller handling stopwatch event.
     */
    public function onKernelTerminate()
    {
        $this->container['stopwatch']->stop('controllerHandling');
    }

    /**
     * Ping current Solr server.
     *
     * @return boolean
     */
    public function pingSolrServer()
    {
        if ($this->isSolrAvailable()) {
            // create a ping query
            $ping = $this->container['solr']->createPing();
            // execute the ping query
            try {
                $result = $this->container['solr']->ping($ping);

                return true;
            } catch (\Exception $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * Resolve current front controller URL.
     *
     * This method is the base of every URL building methods in RZ-CMS.
     * Be careful with handling it.
     *
     * @return string
     */
    private function getResolvedBaseUrl()
    {
        if (isset($_SERVER["SERVER_NAME"])) {
            $url = pathinfo($_SERVER['PHP_SELF']);

            // Protocol
            $pageURL = 'http';
            if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
                $pageURL .= "s";
            }
            $pageURL .= "://";
            // Port
            if (isset($_SERVER["SERVER_PORT"]) && $_SERVER["SERVER_PORT"] != "80") {
                $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"];
            } else {
                $pageURL .= $_SERVER["SERVER_NAME"];
            }
            // Non root folder
            if (!empty($url["dirname"]) && $url["dirname"] != '/') {
                $pageURL .= $url["dirname"];
            }

            return $pageURL;
        } else {
            return false;
        }
    }

    /**
     * @return Symfony\Component\HttpFoundation\Request
     */
    public function getRequest()
    {
        return $this->request;
    }
    /**
     * Alias for Kernel::getEntityManager method.
     *
     * @return Doctrine\ORM\EntityManager
     */
    public function em()
    {
        return $this->container['em'];
    }

    /**
     * Get application debug status.
     *
     * @return boolean
     */
    public function isDebug()
    {
        return (boolean) $this->container['config']['devMode'];
    }

    /**
     * Tell if an Apache Solr server is available,
     * for advanced search engine.
     *
     * @return boolean
     */
    public function isSolrAvailable()
    {
        return (boolean) isset($this->container['config']['solr']['endpoint']);
    }

    /**
     * Get backend application debug status.
     *
     * @return boolean
     */
    public function isBackendDebug()
    {
        return $this->backendDebug;
    }

    /**
     * Return unique instance of Kernel.
     *
     * @return Kernel
     */
    public static function getInstance()
    {
        if (static::$instance === null) {
            static::$instance = new Kernel();
        }

        return static::$instance;
    }

    /**
     * @return Symfony\Component\Stopwatch\Stopwatch
     */
    public function getStopwatch()
    {
        return $this->container['stopwatch'];
    }
}
