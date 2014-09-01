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
use Symfony\Component\Routing;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\Matcher\Dumper\PhpMatcherDumper;
use Symfony\Component\Routing\Generator\Dumper\PhpGeneratorDumper;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\Csrf\CsrfProvider\SessionCsrfProvider;
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
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver;

use Solarium\Client;

/**
 * Kernel.
 */
class Kernel
{
    const SECURITY_DOMAIN = 'rzcms_domain';

    private static $instance = null;
    private $em =           null;
    private $backendDebug = false;
    private $config =       null;

    protected $httpKernel =          null;
    protected $request =             null;
    protected $requestContext =      null;
    protected $response =            null;
    protected $httpUtils =           null;
    protected $context =             null;
    protected $matcher =             null;
    protected $resolver =            null;
    protected $dispatcher =          null;
    protected $urlMatcher =          null;
    protected $urlGenerator =        null;
    protected $stopwatch =           null;
    protected $backendClass =        null;
    protected $frontendThemes =      null;
    protected $rootCollection =      null;
    protected $solrService =         null;

    /*
     * About security and authentification.
     *
     * Authentification is global in RZCMS, but authorization
     * will be handled differently by each available theme.
     *
     */
    protected $csrfProvider =          null;
    protected $userProvider =          null;
    protected $securityContext =       null;
    protected $accessDecisionManager = null;
    protected $authenticationManager = null;

    /**
     * Kernel constructor.
     */
    private final function __construct()
    {
        $this->stopwatch = new Stopwatch();
        $this->stopwatch->start('global');

        $this->stopwatch->start('initKernel');
        $this->parseConfig()
             ->setupEntityManager($this->getConfig());

        $this->rootCollection = new RouteCollection();

        $this->request = Request::createFromGlobals();
        $this->requestContext = new Routing\RequestContext($this->getResolvedBaseUrl());

        $this->dispatcher = new EventDispatcher();
        $this->resolver = new ControllerResolver();
        $this->stopwatch->stop('initKernel');
    }

    /**
     * @return $this
     */
    public function parseConfig()
    {
        $configFile = RENZO_ROOT.'/conf/config.json';
        if (file_exists($configFile)) {
            $this->setConfig(
                json_decode(file_get_contents($configFile), true)
            );
        } else {
            $this->setConfig(null);
        }

        return $this;
    }

    /**
     * Get entities search paths.
     *
     * @return array
     */
    public function getEntitiesPaths()
    {
        return array(
            "src/Renzo/Core/Entities",
            "src/Renzo/Core/AbstractEntities",
            "sources/GeneratedNodeSources"
        );
    }

    /**
     * Initialize Doctrine entity manager.
     * @param array $config
     *
     * @return $this
     */
    public function setupEntityManager($config)
    {
        // the connection configuration
        if ($config !== null) {
            $paths = $this->getEntitiesPaths();

            $dbParams = $config["doctrine"];
            $configDB = Setup::createAnnotationMetadataConfiguration($paths, $this->isDebug());

            $configDB->setProxyDir(RENZO_ROOT . '/sources/Proxies');
            $configDB->setProxyNamespace('Proxies');

            $this->setEntityManager(EntityManager::create($dbParams, $configDB));

            if ($this->em()->getConfiguration()->getResultCacheImpl() !== null) {
                $this->em()->getConfiguration()
                        ->getResultCacheImpl()
                        ->setNamespace($config["appNamespace"]);
            }
            if ($this->em()->getConfiguration()->getHydrationCacheImpl() !== null) {
                $this->em()->getConfiguration()
                        ->getHydrationCacheImpl()
                        ->setNamespace($config["appNamespace"]);
            }
            if ($this->em()->getConfiguration()->getQueryCacheImpl() !== null) {
                $this->em()->getConfiguration()
                        ->getQueryCacheImpl()
                        ->setNamespace($config["appNamespace"]);
            }
            if ($this->em()->getConfiguration()->getMetadataCacheImpl()) {
                $this->em()->getConfiguration()
                        ->getMetadataCacheImpl()
                        ->setNamespace($config["appNamespace"]);
            }
        }

        return $this;
    }
    /**
     * Set kernel Doctrine entity manager and
     * prepare custom data inheritance for Node system.
     *
     * @param EntityManager $em
     *
     * @return Doctrine\ORM\EntityManager
     */
    public function setEntityManager(EntityManager $em)
    {
        $this->em = $em;

        $evm = $this->em->getEventManager();

        /*
         * Create dynamic dicriminator map for our Node system
         */
        $inheritableEntityEvent = new DataInheritanceEvent();
        $evm->addEventListener(Events::loadClassMetadata, $inheritableEntityEvent);

        return $this;
    }

    /**
     * @return RZ\Renzo\Core\Kernel $this
     */
    public function runConsole()
    {
        $this->backendDebug = (boolean) SettingsBag::get('backend_debug');

        if ($this->getConfig() === null ||
            (isset($this->getConfig()['install']) &&
             $this->getConfig()['install'] == true)) {

            $this->prepareSetup();
        } else {
            $this->prepareUrlHandling();
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

        $this->stopwatch->stop('global');

        return $this;
    }

    /**
     * Run main HTTP application.
     *
     * @return RZ\Renzo\Core\Kernel $this
     */
    public function runApp()
    {
        $this->debug = (boolean) SettingsBag::get('debug');
        $this->backendDebug = (boolean) SettingsBag::get('backend_debug');

        $this->httpKernel = new HttpKernel($this->dispatcher, $this->resolver);

        if ($this->getConfig() === null ||
            (isset($this->getConfig()['install']) &&
             $this->getConfig()['install'] == true)) {

            $this->prepareSetup();
        } else {
            $this->prepareRequestHandling();
        }

        try {
            /*
             * ----------------------------
             * Main Framework handle call
             * ----------------------------
             */
            $this->response = $this->httpKernel->handle($this->request);
            $this->response->send();
            $this->httpKernel->terminate($this->request, $this->response);

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
        $feCollection = \Themes\Install\InstallApp::getRoutes();

        if (null !== $feCollection) {

            $this->rootCollection->addCollection($feCollection);
            $this->urlMatcher =   new UrlMatcher($this->rootCollection, $this->requestContext);
            $this->urlGenerator = new UrlGenerator($this->rootCollection, $this->requestContext);
            $this->httpUtils =    new HttpUtils($this->urlGenerator, $this->urlMatcher);

            $this->dispatcher->addSubscriber(new RouterListener($this->urlMatcher));

            $this->dispatcher->addListener(
                KernelEvents::CONTROLLER,
                array(
                    new \RZ\Renzo\Core\Events\ControllerMatchedEvent($this),
                    'onControllerMatched'
                )
            );
        }

        return $this;
    }

    private function prepareRouteCollection()
    {
        /*
         * Add Assets controller routes
         */
        $this->rootCollection->addCollection(\RZ\Renzo\CMS\Controllers\AssetsController::getRoutes());

        /*
         * Add Backend routes
         */
        $beClass = $this->backendClass;
        $cmsCollection = $beClass::getRoutes();
        if ($cmsCollection !== null) {
            $this->rootCollection->addCollection(
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
        foreach ($this->frontendThemes as $theme) {
            $feClass = $theme->getClassName();
            $feCollection = $feClass::getRoutes();
            if ($feCollection !== null) {

                // set host pattern if defined
                if ($theme->getHostname() != '*' &&
                    $theme->getHostname() != '') {

                    $feCollection->setHost($theme->getHostname());
                }
                $this->rootCollection->addCollection($feCollection);
            }
        }

        if (!file_exists(RENZO_ROOT.'/sources/Compiled')) {
            mkdir(RENZO_ROOT.'/sources/Compiled', 0755, true);
        }

        /*
         * Generate custom UrlMatcher
         */
        $dumper = new PhpMatcherDumper($this->rootCollection);
        $class = $dumper->dump(array(
            'class' => 'GlobalUrlMatcher'
        ));
        file_put_contents(RENZO_ROOT.'/sources/Compiled/GlobalUrlMatcher.php', $class);

        /*
         * Generate custom UrlGenerator
         */
        $dumper = new PhpGeneratorDumper($this->rootCollection);
        $class = $dumper->dump(array(
            'class' => 'GlobalUrlGenerator'
        ));
        file_put_contents(RENZO_ROOT.'/sources/Compiled/GlobalUrlGenerator.php', $class);

        return $this;
    }


    /**
     * Prepare URL generation tools.
     *
     * @return this
     */
    private function prepareUrlHandling()
    {
        $this->backendClass = $this->getBackendClass();
        $this->frontendThemes = $this->getFrontendThemes();

        if ($this->isDebug() ||
            !file_exists(RENZO_ROOT.'/sources/Compiled/GlobalUrlMatcher.php') ||
            !file_exists(RENZO_ROOT.'/sources/Compiled/GlobalUrlGenerator.php')) {

            $this->prepareRouteCollection();
        }
        $this->urlMatcher =   new MixedUrlMatcher($this->requestContext);
        $this->urlGenerator = new \GlobalUrlGenerator($this->requestContext);
        $this->httpUtils =    new HttpUtils($this->urlGenerator, $this->urlMatcher);

        return $this;
    }

    /**
     * Prepare Translation generation tools.
     */
    private function prepareTranslation()
    {
        /*
         * set default locale
         */
        $translation = Kernel::getInstance()->em()
            ->getRepository('RZ\Renzo\Core\Entities\Translation')
            ->findDefault();

        if ($translation !== null) {
            $shortLocale = $translation->getShortLocale();
            $this->getRequest()->setLocale($shortLocale);
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
        $this->stopwatch->start('prepareTranslation');
        $this->prepareTranslation();
        $this->stopwatch->stop('prepareTranslation');

        $this->stopwatch->start('prepareRouting');
        $this->prepareUrlHandling();
        $this->stopwatch->stop('prepareRouting');

        $this->dispatcher->addSubscriber(new RouterListener($this->urlMatcher));

        /*
         * Security
         */
        $this->stopwatch->start('sessionSecurity');
        // Create session and CsrfProvider
        $this->initializeSession();
        // Create security context with RZCMS User/Roles system
        $this->initializeSecurityContext();
        $this->stopwatch->stop('sessionSecurity');

        $this->stopwatch->start('firewall');
        $map = new FirewallMap();
        // Register back-end security scheme
        $beClass = $this->backendClass;
        $beClass::appendToFirewallMap(
            $this->getSecurityContext(),
            $this->getUserProvider(),
            $this->getAuthenticationManager(),
            $this->getAccessDecisionManager(),
            $map,
            $this->httpKernel,
            $this->httpUtils,
            $this->dispatcher
        );

        // Register front-end security scheme
        foreach ($this->frontendThemes as $theme) {
            $feClass = $theme->getClassName();
            $feClass::appendToFirewallMap(
                $this->getSecurityContext(),
                $this->getUserProvider(),
                $this->getAuthenticationManager(),
                $this->getAccessDecisionManager(),
                $map,
                $this->httpKernel,
                $this->httpUtils,
                $this->dispatcher
            );
        }

        $firewall = new Firewall($map, $this->dispatcher);
        $this->stopwatch->stop('firewall');

        /*
         * Events
         */
        $this->dispatcher->addListener(
            KernelEvents::REQUEST,
            array(
                $this,
                'onStartKernelRequest'
            )
        );
        $this->dispatcher->addListener(
            KernelEvents::REQUEST,
            array(
                $firewall,
                'onKernelRequest'
            )
        );
        /*
         * Register after controller matched listener
         */
        $this->dispatcher->addListener(
            KernelEvents::CONTROLLER,
            array(
                $this,
                'onControllerMatched'
            )
        );
        $this->dispatcher->addListener(
            KernelEvents::CONTROLLER,
            array(
                new \RZ\Renzo\Core\Events\ControllerMatchedEvent($this),
                'onControllerMatched'
            )
        );
        $this->dispatcher->addListener(
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
            $this->dispatcher->addSubscriber(new \RZ\Renzo\Core\Utils\DebugPanel());
        }
    }
    /**
     * Start a stopwatch event when a kernel start handling.
     */
    public function onStartKernelRequest()
    {
        $this->stopwatch->start('requestHandling');
    }
    /**
     * Stop request-handling stopwatch event and
     * start a new stopwatch event when a controller is instanciated.
     */
    public function onControllerMatched()
    {
        $this->stopwatch->stop('matchingRoute');
        $this->stopwatch->stop('requestHandling');
        $this->stopwatch->start('controllerHandling');
    }
    /**
     * Stop controller handling stopwatch event.
     */
    public function onKernelTerminate()
    {
        $this->stopwatch->stop('controllerHandling');
    }

    /**
     * Get frontend app themes.
     *
     * @return ArrayCollection of RZ\Renzo\Core\Entities\Theme
     */
    private function getFrontendThemes()
    {
        $themes = $this->em()
            ->getRepository('RZ\Renzo\Core\Entities\Theme')
            ->findBy(array('available'=>true, 'backendTheme'=>false));

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
    }

    /**
     * Get backend app controller full-qualified classname.
     *
     * Return 'RZ\Renzo\CMS\Controllers\BackendController' if none found in database.
     *
     * @return string
     */
    private function getBackendClass()
    {
        $theme = $this->em()
            ->getRepository('RZ\Renzo\Core\Entities\Theme')
            ->findOneBy(array('available'=>true, 'backendTheme'=>true));

        if ($theme !== null) {
            return $theme->getClassName();
        }

        return 'RZ\Renzo\CMS\Controllers\BackendController';
    }

    /**
     * Get Solr service if available.
     *
     *
     * @return Apache_Solr_Service or null
     */
    public function getSolrService()
    {
        if ($this->isSolrAvailable()) {
            if (null === $this->solrService) {

                $this->solrService = new \Solarium\Client($this->getConfig()['solr']);
                $this->solrService->setDefaultEndpoint('localhost');
            }

            return $this->solrService;
        }

        return null;
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
            $ping = $this->getSolrService()->createPing();
            // execute the ping query
            try {
                $result = $this->getSolrService()->ping($ping);

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

    private function initializeSession()
    {
        // créer un objet session depuis le composant HttpFoundation
        $this->getRequest()->setSession(new Session());

        // générer le secret CSRF depuis quelque part
        $csrfSecret = $this->getConfig()["security"]['secret'];
        $this->csrfProvider = new SessionCsrfProvider(
            $this->getRequest()->getSession(),
            $csrfSecret
        );
    }

    private function initializeSecurityContext()
    {
        $this->userProvider = new UserProvider();
        $this->authenticationManager = new DaoAuthenticationProvider(
            $this->userProvider,
            new UserChecker(),
            static::SECURITY_DOMAIN,
            UserHandler::getEncoderFactory()
        );
        $this->accessDecisionManager = new AccessDecisionManager(
            array(
                new RoleVoter('ROLE_')
            )
        );
        $this->securityContext = new SecurityContext(
            $this->authenticationManager,
            $this->accessDecisionManager
        );
    }
    /**
     * @return Symfony\Component\Form\Extension\Csrf\CsrfProvider\SessionCsrfProvider
     */
    public function getCsrfProvider()
    {
        return $this->csrfProvider;
    }
    /**
     * @return \RZ\Renzo\Core\Handlers\UserProvider
     */
    public function getUserProvider()
    {
        return $this->userProvider;
    }
    /**
     * @return \Symfony\Component\Security\Core\SecurityContext
     */
    public function getSecurityContext()
    {
        return $this->securityContext;
    }
    /**
     * @return Symfony\Component\Security\Core\Authorization\AccessDecisionManager
     */
    public function getAccessDecisionManager()
    {
        return $this->accessDecisionManager;
    }
    /**
     * @return Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider
     */
    public function getAuthenticationManager()
    {
        return $this->authenticationManager;
    }
    /**
     * @return Symfony\Component\HttpFoundation\Request
     */
    public function getRequest()
    {
        return $this->request;
    }
    /**
     * @return Symfony\Component\Routing\Generator\UrlGenerator
     */
    public function getUrlGenerator()
    {
        return $this->urlGenerator;
    }
    /**
     * @return Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        return $this->em;
    }
    /**
     * Alias for Kernel::getEntityManager method.
     *
     * @return Doctrine\ORM\EntityManager
     */
    public function em()
    {
        return $this->em;
    }
    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param array $config
     *
     * @return $config
     */
    public function setConfig($config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Get application debug status.
     *
     * @return boolean
     */
    public function isDebug()
    {
        return (boolean) $this->getConfig()['devMode'];
    }

    /**
     * Tell if an Apache Solr server is available,
     * for advanced search engine.
     *
     * @return boolean
     */
    public function isSolrAvailable()
    {
        if (isset($this->getConfig()['solr']['endpoint'])) {

            return true;
        }

        return false;
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
        return $this->stopwatch;
    }
}
