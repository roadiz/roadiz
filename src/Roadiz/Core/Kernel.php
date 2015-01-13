<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
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
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file Kernel.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core;

use RZ\Roadiz\Core\Routing\MixedUrlMatcher;
use RZ\Roadiz\Core\Bags\SettingsBag;
use RZ\Roadiz\Core\Services\SecurityServiceProvider;
use RZ\Roadiz\Core\Services\FormServiceProvider;
use RZ\Roadiz\Core\Services\RoutingServiceProvider;
use RZ\Roadiz\Core\Services\DoctrineServiceProvider;
use RZ\Roadiz\Core\Services\ConfigurationServiceProvider;
use RZ\Roadiz\Core\Services\SolrServiceProvider;
use RZ\Roadiz\Core\Services\EmbedDocumentsServiceProvider;
use RZ\Roadiz\Core\Services\TwigServiceProvider;
use RZ\Roadiz\Core\Services\EntityApiServiceProvider;
use RZ\Roadiz\Core\Services\BackofficeServiceProvider;
use RZ\Roadiz\Core\Services\ThemeServiceProvider;
use RZ\Roadiz\Core\Services\TranslationServiceProvider;

use Symfony\Component\Console\Application;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\Dumper\PhpMatcherDumper;
use Symfony\Component\Routing\Generator\Dumper\PhpGeneratorDumper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Security\Http\HttpUtils;

use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Helper\ProgressHelper;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;

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
    public static $cmsVersion = "0.1.0";
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
        $this->request = Request::createFromGlobals();

        /*
         * Register current Kernel as a service provider.
         */
        $this->container->register($this);
        $this->container['stopwatch']->openSection();
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
            $rc->setHost($this->request->server->get('HTTP_HOST'));
            $rc->setHttpPort(intval($this->request->server->get('SERVER_PORT')));

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
        $container->register(new BackofficeServiceProvider());
        $container->register(new ThemeServiceProvider());
        $container->register(new TranslationServiceProvider());
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
        $helperSet = new HelperSet(array(
            'db' => new ConnectionHelper($this->container['em']->getConnection()),
            'em' => new EntityManagerHelper($this->container['em']),
            'dialog' => new DialogHelper(),
            'progress' => new ProgressHelper()
        ));
        $application->setHelperSet($helperSet);

        $application->add(new \RZ\Roadiz\Console\TranslationsCommand);
        $application->add(new \RZ\Roadiz\Console\NodeTypesCommand);
        $application->add(new \RZ\Roadiz\Console\NodesCommand);
        $application->add(new \RZ\Roadiz\Console\ThemesCommand);
        $application->add(new \RZ\Roadiz\Console\InstallCommand);
        $application->add(new \RZ\Roadiz\Console\UsersCommand);
        $application->add(new \RZ\Roadiz\Console\RequirementsCommand);
        $application->add(new \RZ\Roadiz\Console\SolrCommand);
        $application->add(new \RZ\Roadiz\Console\CacheCommand);
        $application->add(new \RZ\Roadiz\Console\ConfigurationCommand);

        // Use default Doctrine commands
        ConsoleRunner::addCommands($application);

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
            true === (boolean) $this->container['config']['install'])) {
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
        try {
            if ($this->isDebug() ||
                !file_exists(ROADIZ_ROOT.'/gen-src/Compiled/GlobalUrlMatcher.php') ||
                !file_exists(ROADIZ_ROOT.'/gen-src/Compiled/GlobalUrlGenerator.php')) {
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

            if (!$this->isInstallMode()) {
                $this->prepareRequestHandling();
            }

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
        } catch (\RZ\Roadiz\Core\Exceptions\NoConfigurationFoundException $e) {
            echo $e->getMessage().PHP_EOL;
        }

        return $this;
    }

    /**
     * Save a compiled version of UrlMatcher and UrlGenerator.
     */
    protected function dumpUrlUtils()
    {
        if (!file_exists(ROADIZ_ROOT.'/gen-src/Compiled')) {
            mkdir(ROADIZ_ROOT.'/gen-src/Compiled', 0755, true);
        }

        /*
         * Generate custom UrlMatcher
         */
        $dumper = new PhpMatcherDumper($this->container['routeCollection']);
        $class = $dumper->dump(array(
            'class' => 'GlobalUrlMatcher'
        ));
        file_put_contents(ROADIZ_ROOT.'/gen-src/Compiled/GlobalUrlMatcher.php', $class);

        /*
         * Generate custom UrlGenerator
         */
        $dumper = new PhpGeneratorDumper($this->container['routeCollection']);
        $class = $dumper->dump(array(
            'class' => 'GlobalUrlGenerator'
        ));
        file_put_contents(ROADIZ_ROOT.'/gen-src/Compiled/GlobalUrlGenerator.php', $class);
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
        if (true === (boolean) SettingsBag::get('display_debug_panel')) {
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
                $this->container['solr']->ping($ping);

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
        if ($this->request->server->get('SERVER_NAME')) {

            // Remove everything after index.php in php_self
            // when using PHP dev servers
            $url = pathinfo(substr(
                $this->request->server->get('PHP_SELF'),
                0,
                strpos($this->request->server->get('PHP_SELF'), '.php')
            ));

            // Protocol
            $pageURL = 'http';
            if ($this->request->server->get('HTTPS') &&
                $this->request->server->get('HTTPS') == "on") {
                $pageURL .= "s";
            }
            $pageURL .= "://";
            // Port
            if ($this->request->server->get('SERVER_PORT') &&
                $this->request->server->get('SERVER_PORT') != "80") {
                $pageURL .= $this->request->server->get('SERVER_NAME').
                            ":".
                            $this->request->server->get('SERVER_PORT');
            } else {
                $pageURL .= $this->request->server->get('SERVER_NAME');
            }
            // Non root folder
            if (!empty($url["dirname"]) &&
                $url["dirname"] != '/') {
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
        return isset($this->container['solr']) && null !== $this->container['solr'];
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
