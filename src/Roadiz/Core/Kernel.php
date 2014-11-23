<?php
/**
 * Copyright © 2014, REZO ZERO
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file Kernel.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core;

use RZ\Roadiz\Core\Routing\MixedUrlMatcher;
use RZ\Roadiz\Core\Bags\SettingsBag;
use RZ\Roadiz\Core\Entities\Theme;
use RZ\Roadiz\Core\Services\SecurityServiceProvider;
use RZ\Roadiz\Core\Services\FormServiceProvider;
use RZ\Roadiz\Core\Services\RoutingServiceProvider;
use RZ\Roadiz\Core\Services\DoctrineServiceProvider;
use RZ\Roadiz\Core\Services\ConfigurationServiceProvider;
use RZ\Roadiz\Core\Services\SolrServiceProvider;
use RZ\Roadiz\Core\Services\EmbedDocumentsServiceProvider;
use RZ\Roadiz\Core\Services\TwigServiceProvider;
use RZ\Roadiz\Core\Services\EntityApiServiceProvider;

use Symfony\Component\Console\Application;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Matcher\Dumper\PhpMatcherDumper;
use Symfony\Component\Routing\Generator\Dumper\PhpGeneratorDumper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Firewall;

use Pimple\Container;

/**
 * Main roadiz CMS entry point.
 */
class Kernel implements \Pimple\ServiceProviderInterface
{
    const CMS_VERSION =         'alpha';
    const SECURITY_DOMAIN =     'roadiz_domain';
    const INSTALL_CLASSNAME =   '\\Themes\\Install\\InstallApp';

    public static $cmsBuild =   null;
    public static $cmsVersion = "1.0.1";
    private static $instance =  null;

    public $container =         null;
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

        $this->container['stopwatch']->openSection();

        $this->request = Request::createFromGlobals();
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
     * Register every services needed by Roadiz CMS.
     *
     * @param Container $container
     */
    public function register(Container $container)
    {
        $container['stopwatch'] = function ($c) {
            return new Stopwatch();
        };

        $container['dispatcher'] = function ($c) {

            $dispatcher = new EventDispatcher();
            $dispatcher->addSubscriber(new RouterListener($c['urlMatcher']));
            $dispatcher->addListener(
                KernelEvents::CONTROLLER,
                array(
                    new \RZ\Roadiz\Core\Events\ControllerMatchedEvent($this),
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
        $container['requestContext'] = function ($c) {
            $rc = new RequestContext($this->getResolvedBaseUrl());
            $rc->setHost($this->getRequest()->server->get('HTTP_HOST'));
            $rc->setHttpPort(intval($this->getRequest()->server->get('SERVER_PORT')));

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

        $container->register(new ConfigurationServiceProvider());
        $container->register(new SecurityServiceProvider());
        $container->register(new FormServiceProvider());
        $container->register(new RoutingServiceProvider());
        $container->register(new DoctrineServiceProvider());
        $container->register(new SolrServiceProvider());
        $container->register(new EmbedDocumentsServiceProvider());
        $container->register(new TwigServiceProvider());
        $container->register(new EntityApiServiceProvider());
    }

    /**
     * @return RZ\Roadiz\Core\Kernel $this
     */
    public function runConsole()
    {
        /*
         * Define a request wide timezone
         */
        if (!empty($this->container['config']["timezone"])) {
            date_default_timezone_set($this->container['config']["timezone"]);
        } else {
            date_default_timezone_set("Europe/Paris");
        }

        $application = new Application('Roadiz Console Application', '0.1');
        $application->add(new \RZ\Roadiz\Console\TranslationsCommand);
        $application->add(new \RZ\Roadiz\Console\NodeTypesCommand);
        $application->add(new \RZ\Roadiz\Console\NodesCommand);
        $application->add(new \RZ\Roadiz\Console\SchemaCommand);
        $application->add(new \RZ\Roadiz\Console\ThemesCommand);
        $application->add(new \RZ\Roadiz\Console\InstallCommand);
        $application->add(new \RZ\Roadiz\Console\UsersCommand);
        $application->add(new \RZ\Roadiz\Console\RequirementsCommand);
        $application->add(new \RZ\Roadiz\Console\SolrCommand);
        $application->add(new \RZ\Roadiz\Console\CacheCommand);

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
     * @return RZ\Roadiz\Core\Kernel $this
     */
    public function runApp()
    {
        if ($this->isDebug() ||
            !file_exists(RENZO_ROOT.'/gen-src/Compiled/GlobalUrlMatcher.php') ||
            !file_exists(RENZO_ROOT.'/gen-src/Compiled/GlobalUrlGenerator.php')) {

            $this->container['stopwatch']->start('dumpUrlUtils');
            $this->dumpUrlUtils();
            $this->container['stopwatch']->stop('dumpUrlUtils');
        }
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
            $this->prepareRequestHandling();
        }

        try {
            /*
             * ----------------------------
             * Main Framework handle call
             * ----------------------------
             */
            $this->response = $this->container['httpKernel']->handle($this->request);
            $this->response->setCharset('UTF-8');
            $this->response->prepare($this->request);

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
        if (!file_exists(RENZO_ROOT.'/gen-src/Compiled')) {
            mkdir(RENZO_ROOT.'/gen-src/Compiled', 0755, true);
        }

        /*
         * Generate custom UrlMatcher
         */
        $dumper = new PhpMatcherDumper($this->container['routeCollection']);
        $class = $dumper->dump(array(
            'class' => 'GlobalUrlMatcher'
        ));
        file_put_contents(RENZO_ROOT.'/gen-src/Compiled/GlobalUrlMatcher.php', $class);

        /*
         * Generate custom UrlGenerator
         */
        $dumper = new PhpGeneratorDumper($this->container['routeCollection']);
        $class = $dumper->dump(array(
            'class' => 'GlobalUrlGenerator'
        ));
        file_put_contents(RENZO_ROOT.'/gen-src/Compiled/GlobalUrlGenerator.php', $class);
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
                            ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                            ->findDefault();

        if ($translation !== null) {
            $shortLocale = $translation->getLocale();
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

        $this->container['stopwatch']->start('initThemes');

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
                $this->container['firewall'],
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
            $this->container['dispatcher']->addSubscriber(new \RZ\Roadiz\Core\Utils\DebugPanel());
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
        if ($this->container['stopwatch']->isStarted('controllerHandling')) {
            $this->container['stopwatch']->stop('controllerHandling');
        }
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
    public function getResolvedBaseUrl()
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
        return (boolean) $this->container['config']['devMode'] ||
               (boolean) $this->container['config']['install'];
    }

    /**
     * Tell if an Apache Solr server is available,
     * for advanced search engine.
     *
     * @return boolean
     */
    public function isSolrAvailable()
    {
        return isset($this->container['solr']);
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
