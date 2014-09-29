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

use RZ\Renzo\Core\Routing\MixedUrlMatcher;
use RZ\Renzo\Core\Bags\SettingsBag;
use RZ\Renzo\Core\Entities\Theme;
use RZ\Renzo\Core\Services\SecurityServiceProvider;
use RZ\Renzo\Core\Services\FormServiceProvider;
use RZ\Renzo\Core\Services\RoutingServiceProvider;
use RZ\Renzo\Core\Services\DoctrineServiceProvider;

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
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Firewall;
use Pimple\Container;
use Solarium\Client;

/**
 * Main renzo CMS entry point.
 */
class Kernel implements \Pimple\ServiceProviderInterface
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

        /*
         * Register current Kernel as a service provider.
         */
        $this->container->register($this);

        $this->container['stopwatch']->start('global');
        $this->container['stopwatch']->start('initKernel');

        $this->request = Request::createFromGlobals();

        $this->container['stopwatch']->stop('initKernel');


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

    /**
     * Register every services needed by Renzo CMS.
     *
     * @param Container $container
     */
    public function register(Container $container)
    {
        $container['stopwatch'] = function ($c) {
            return new Stopwatch();
        };
        /*
         * Inject app config
         */
        $container['config'] = function ($c) {
            $configFile = RENZO_ROOT.'/conf/config.json';
            if (file_exists($configFile)) {
                return json_decode(file_get_contents($configFile), true);
            } else {
                return null;
            }
        };

        $container['dispatcher'] = function ($c) {

            $dispatcher = new EventDispatcher();
            $dispatcher->addSubscriber(new RouterListener($this->container['urlMatcher']));
            $dispatcher->addListener(
                KernelEvents::CONTROLLER,
                array(
                    new \RZ\Renzo\Core\Events\ControllerMatchedEvent($this),
                    'onControllerMatched'
                )
            );

            return $dispatcher;
        };
        $container['resolver'] = function ($c) {
            return new ControllerResolver();
        };
        $container['httpKernel'] = function ($c) {
            return new HttpKernel($c['dispatcher'], $c['resolver']);
        };
        $container['entitiesPaths'] = array(
            "src/Renzo/Core/Entities",
            "src/Renzo/Core/AbstractEntities",
            "sources/GeneratedNodeSources"
        );

        $this->setupSolrService();

        $container->register(new SecurityServiceProvider());
        $container->register(new FormServiceProvider());
        $container->register(new RoutingServiceProvider());
        $container->register(new DoctrineServiceProvider());

        if (!$this->isInstallMode()) {
            $container->register(new RoutingServiceProvider());
        } else {
            $container['routeCollection'] = function ($c) {

                $installClassname = static::INSTALL_CLASSNAME;
                $feCollection = $installClassname::getRoutes();
                $rCollection = new RouteCollection();
                $rCollection->addCollection($feCollection);

                return $rCollection;
            };
        }

        $container['requestContext'] = function ($c) {
            $rc = new Routing\RequestContext(Kernel::getInstance()->getResolvedBaseUrl());
            $rc->setHost(Kernel::getInstance()->getRequest()->server->get('HTTP_HOST'));
            $rc->setHttpPort(intval(Kernel::getInstance()->getRequest()->server->get('SERVER_PORT')));

            return $rc;
        };
        $container['urlMatcher'] = function ($c) {
            return new MixedUrlMatcher($c['requestContext']);
        };
        $container['urlGenerator'] = function ($c) {
            return new \GlobalUrlGenerator($c['requestContext']);
        };
        $container['httpUtils'] = function ($c) {
            return new HttpUtils($c['urlGenerator'], $c['urlMatcher']);
        };

        $container['logger'] = function ($c) {
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

            // nothing to prepare

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
     * Get application debug status.
     *
     * @return boolean
     */
    public function isDebug()
    {
        return (boolean) $this->container['config']['devMode'] || (boolean) $this->container['config']['install'];
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
}
