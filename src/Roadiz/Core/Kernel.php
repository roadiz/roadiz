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

use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Core\Bags\SettingsBag;
use RZ\Roadiz\Core\Events\RouteCollectionSubscriber;
use RZ\Roadiz\Core\HttpFoundation\Request;
use RZ\Roadiz\Utils\DebugPanel;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\ProgressHelper;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Yaml\Parser;

/**
 * Main roadiz CMS entry point.
 */
class Kernel implements ServiceProviderInterface
{
    const CMS_VERSION = 'alpha';
    const SECURITY_DOMAIN = 'roadiz_domain';
    const INSTALL_CLASSNAME = '\\Themes\\Install\\InstallApp';

    public static $cmsBuild = null;
    public static $cmsVersion = "0.7.1";
    private static $instance = null;

    public $container = null;
    protected $response = null;

    /**
     * Kernel constructor.
     *
     * This method must not throw any exceptions.
     */
    final private function __construct()
    {
        $this->container = new Container();
    }

    /**
     * Boot every kernel services.
     *
     * @throws RZ\Roadiz\Core\Exceptions\NoConfigurationFoundException
     */
    public function boot()
    {
        /*
         * Register current Kernel as a service provider.
         */
        $this->container->register($this);
        $this->container['stopwatch']->openSection();

        $this->initEvents();
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

        $container['debugPanel'] = function ($c) {
            return new DebugPanel($c['twig.environment'], $c['stopwatch']);
        };

        $container['dispatcher'] = function ($c) {
            return new EventDispatcher();
        };

        $container['request'] = function ($c) {
            return Request::createFromGlobals();
        };

        $container['requestContext'] = function ($c) {
            $rc = new RequestContext($c['request']->getResolvedBasePath());
            $rc->setHost($c['request']->server->get('HTTP_HOST'));

            return $rc;
        };

        /*
         * Load service providers from conf/services.yml
         *
         * Edit this file if you want to customize Roadiz services
         * behaviour.
         */
        $yaml = new Parser();
        $services = $yaml->parse(file_get_contents(ROADIZ_ROOT . '/conf/services.yml'));
        foreach ($services['providers'] as $providerClass) {
            $container->register(new $providerClass());
        }
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

        $application = new Application('Roadiz Console Application', static::$cmsVersion);
        $helperSet = new HelperSet([
            'db' => new ConnectionHelper($this->container['em']->getConnection()),
            'em' => new EntityManagerHelper($this->container['em']),
            'dialog' => new DialogHelper(),
            'progress' => new ProgressHelper(),
        ]);
        $application->setHelperSet($helperSet);

        $application->add(new \RZ\Roadiz\Console\TranslationsCommand);
        $application->add(new \RZ\Roadiz\Console\NodeTypesCommand);
        $application->add(new \RZ\Roadiz\Console\NodesSourcesCommand);
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
            /*
             * Define a request wide timezone
             */
            if (!empty($this->container['config']["timezone"])) {
                date_default_timezone_set($this->container['config']["timezone"]);
            } else {
                date_default_timezone_set("Europe/Paris");
            }

            /*
             * ----------------------------
             * Main Framework handle call
             * ----------------------------
             */
            $this->response = $this->container['httpKernel']->handle($this->container['request']);

        } catch (\RZ\Roadiz\Core\Exceptions\NoTranslationAvailableException $e) {
            $this->response = $this->getEmergencyResponse($e);
        } catch (\Symfony\Component\Routing\Exception\ResourceNotFoundException $e) {
            $this->response = $this->getEmergencyResponse($e);
        } catch (\RZ\Roadiz\Core\Exceptions\NoConfigurationFoundException $e) {
            $this->response = $this->getEmergencyResponse($e);
        } catch (\Exception $e) {
            $this->response = $this->getEmergencyResponse($e);
        }

        $this->response->prepare($this->container['request']);
        $this->response->send();
        $this->container['httpKernel']->terminate($this->container['request'], $this->response);

        return $this;
    }

    /**
     * Create an emergency response to be sent instead of error logs.
     *
     * @param \Exception $e
     *
     * @return Response
     */
    public function getEmergencyResponse($e)
    {
        /*
         * Log error before displaying a fallback page.
         */
        if (isset($this->container['logger'])) {
            $this->container['logger']->emerg($e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'exception' => get_class($e),
            ]);
        }

        if ($this->container['request']->isXmlHttpRequest()) {
            return new \Symfony\Component\HttpFoundation\JsonResponse(
                [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'exception' => get_class($e),
                ],
                Response::HTTP_SERVICE_UNAVAILABLE
            );

        } else {
            $html = file_get_contents(ROADIZ_ROOT . '/src/Roadiz/CMS/Resources/views/emerg.html');
            $html = str_replace('{{ message }}', $e->getMessage(), $html);

            if ($this->isDebug()) {
                $trace = preg_replace('#([^\n]+)#', '<p>$1</p>', $e->getTraceAsString());
                $html = str_replace('{{ details }}', $trace, $html);
            } else {
                $html = str_replace('{{ details }}', '', $html);
            }

            return new Response(
                $html,
                Response::HTTP_SERVICE_UNAVAILABLE,
                ['content-type' => 'text/html']
            );
        }
    }

    /**
     * Prepare Translation generation tools.
     */
    public function onKernelRequest()
    {
        /*
         * Register Themes dependency injection
         */
        if (!$this->isInstallMode()) {
            // Register back-end security scheme
            $beClass = $this->container['backendClass'];
            $beClass::setupDependencyInjection($this->container);

            /*
             * Set default locale
             */
            $translation = $this->container['em']
                                ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                                ->findDefault();

            if ($translation !== null) {
                $shortLocale = $translation->getLocale();
                $this->container['request']->setLocale($shortLocale);
                \Locale::setDefault($shortLocale);
            }
        }

        // Register front-end security scheme
        foreach ($this->container['frontendThemes'] as $theme) {
            $feClass = $theme->getClassName();
            $feClass::setupDependencyInjection($this->container);
        }
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $response = $event->getResponse();
        $response->setCharset('UTF-8');
        $event->setResponse($response);
    }

    /**
     * Prepare backend and frontend routes and logic.
     *
     * @return boolean
     */
    private function initEvents()
    {
        if ($this->isDebug() || RouteCollectionSubscriber::needToDumpUrlTools()) {
            $this->container['dispatcher']->addSubscriber(
                new RouteCollectionSubscriber($this->container['routeCollection'], $this->container['stopwatch'])
            );
        }

        $this->container['dispatcher']->addSubscriber(new RouterListener($this->container['urlMatcher']));

        /*
         * Events
         */
        $this->container['dispatcher']->addListener(
            KernelEvents::REQUEST,
            [
                $this,
                'onKernelRequest',
            ]
        );
        $this->container['dispatcher']->addListener(
            KernelEvents::REQUEST,
            [
                $this->container['firewall'],
                'onKernelRequest',
            ]
        );
        $this->container['dispatcher']->addListener(
            KernelEvents::CONTROLLER,
            [
                new \RZ\Roadiz\Core\Events\ControllerMatchedEvent($this),
                'onControllerMatched',
            ]
        );
        $this->container['dispatcher']->addListener(
            KernelEvents::RESPONSE,
            [
                $this,
                'onKernelResponse',
            ]
        );

        /*
         * If debug, alter HTML responses to append Debug panel to view
         */
        if (true === (boolean) SettingsBag::get('display_debug_panel')) {
            $this->container['dispatcher']->addSubscriber($this->container['debugPanel']);
        }
    }

    /**
     * Get a FQDN base url for static resources.
     *
     * You should fill “static_domain_name” setting after your
     * static domain name. Do not forget to create a virtual host
     * for this domain to serve the same content as your primary domain.
     *
     * @return string
     */
    public function getStaticBaseUrl()
    {
        return $this->convertUrlToStaticDomainUrl($this->container['request']->getResolvedBaseUrl());
    }

    /**
     * @param  string $url Absolute Url with primary domain.
     * @return string      Absolute Url with static domain.
     */
    public function convertUrlToStaticDomainUrl($url)
    {
        $staticDomain = SettingsBag::get('static_domain_name');

        if (!empty($staticDomain)) {
            return preg_replace('#://([^:^/]+)#', '://' . $staticDomain, $url);
        } else {
            return $url;
        }
    }

    /**
     * @return Pimple\Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @return Symfony\Component\HttpFoundation\Request
     */
    public function getRequest()
    {
        return $this->container['request'];
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
