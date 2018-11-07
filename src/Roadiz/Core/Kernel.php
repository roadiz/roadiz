<?php
/**
 * Copyright © 2016, Ambroise Maupate and Julien Blanchet
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

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\CMS\Controllers\AssetsController;
use RZ\Roadiz\Core\Events\ControllerMatchedSubscriber;
use RZ\Roadiz\Core\Events\DebugBarSubscriber;
use RZ\Roadiz\Core\Events\ExceptionSubscriber;
use RZ\Roadiz\Core\Events\LocaleSubscriber;
use RZ\Roadiz\Core\Events\MaintenanceModeSubscriber;
use RZ\Roadiz\Core\Events\PimpleDumperSubscriber;
use RZ\Roadiz\Core\Events\PreviewBarSubscriber;
use RZ\Roadiz\Core\Events\PreviewModeSubscriber;
use RZ\Roadiz\Core\Events\SignatureListener;
use RZ\Roadiz\Core\Events\ThemesSubscriber;
use RZ\Roadiz\Core\Events\UserLocaleSubscriber;
use RZ\Roadiz\Core\Exceptions\NoConfigurationFoundException;
use RZ\Roadiz\Core\Models\FileAwareInterface;
use RZ\Roadiz\Core\Services\AssetsServiceProvider;
use RZ\Roadiz\Core\Services\BackofficeServiceProvider;
use RZ\Roadiz\Core\Services\BagsServiceProvider;
use RZ\Roadiz\Core\Services\DebugServiceProvider;
use RZ\Roadiz\Core\Services\DoctrineServiceProvider;
use RZ\Roadiz\Core\Services\EmbedDocumentsServiceProvider;
use RZ\Roadiz\Core\Services\EntityApiServiceProvider;
use RZ\Roadiz\Core\Services\FactoryServiceProvider;
use RZ\Roadiz\Core\Services\FormServiceProvider;
use RZ\Roadiz\Core\Services\LoggerServiceProvider;
use RZ\Roadiz\Core\Services\MailerServiceProvider;
use RZ\Roadiz\Core\Services\RoutingServiceProvider;
use RZ\Roadiz\Core\Services\SecurityServiceProvider;
use RZ\Roadiz\Core\Services\SolrServiceProvider;
use RZ\Roadiz\Core\Services\ThemeServiceProvider;
use RZ\Roadiz\Core\Services\TranslationServiceProvider;
use RZ\Roadiz\Core\Services\TwigServiceProvider;
use RZ\Roadiz\Core\Services\YamlConfigurationServiceProvider;
use RZ\Roadiz\Core\Viewers\ExceptionViewer;
use RZ\Roadiz\Utils\DebugBar\NullStopwatch;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\EventListener\ResponseListener;
use Symfony\Component\HttpKernel\EventListener\SaveSessionListener;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Themes\Install\InstallApp;

/**
 *
 */
class Kernel implements ServiceProviderInterface, KernelInterface, TerminableInterface, ContainerAwareInterface, FileAwareInterface
{
    const CMS_VERSION = 'beta';
    const SECURITY_DOMAIN = 'roadiz_domain';
    const INSTALL_CLASSNAME = InstallApp::class;

    public static $cmsBuild = null;
    public static $cmsVersion = "0.22.23";

    /**
     * @var Container|null
     */
    public $container = null;
    protected $environment;
    protected $debug;
    protected $preview;
    protected $booted = false;
    protected $rootDir;
    protected $name;
    protected $startTime;

    /**
     * @param string $environment
     * @param boolean $debug
     * @param bool $preview
     */
    public function __construct($environment, $debug, $preview = false)
    {
        $this->environment = $environment;
        $this->preview = (boolean) $preview;
        $this->debug = (boolean) $debug;
        $this->rootDir = $this->getRootDir();
        $this->name = $this->getName();

        if ($this->debug) {
            $this->startTime = microtime(true);
        }
    }

    /**
     * Boots the current kernel.
     */
    public function boot()
    {
        if (true === $this->booted) {
            return;
        }

        try {
            /*
             * Register current Kernel as a service provider.
             */
            $this->container = new Container();
            $this->container->register($this);
            /*
             * Following PHP customization should only use
             * not-required configuration elements.
             */
            @date_default_timezone_set($this->container['config']["timezone"]);
            @ini_set('session.name', $this->container['config']["security"]["session_name"]);
            @ini_set('session.cookie_secure', $this->container['config']["security"]["session_cookie_secure"]);
            @ini_set('session.cookie_httponly', $this->container['config']["security"]["session_cookie_httponly"]);
            $this->booted = true;
        } catch (InvalidConfigurationException $e) {
            $view = new ExceptionViewer();
            $response = $view->getResponse($e, Request::createFromGlobals(), $this->isDebug());
            $response->send();
        }
    }

    /**
     * Register every services needed by Roadiz CMS.
     *
     * @param Container $container
     */
    public function register(Container $container)
    {
        $container['stopwatch'] = function () {
            if ($this->isDebug()) {
                return new Stopwatch();
            }
            return new NullStopwatch();
        };

        $container['dispatcher'] = function () {
            return new EventDispatcher();
        };

        $container['kernel'] = $this;
        $container['stopwatch']->openSection();
        $container['stopwatch']->start('registerServices');

        $container->register(new YamlConfigurationServiceProvider());
        $container->register(new AssetsServiceProvider());
        $container->register(new BackofficeServiceProvider());
        $container->register(new DoctrineServiceProvider());
        $container->register(new EmbedDocumentsServiceProvider());
        $container->register(new EntityApiServiceProvider());
        $container->register(new FormServiceProvider());
        $container->register(new MailerServiceProvider());
        $container->register(new RoutingServiceProvider());
        $container->register(new SecurityServiceProvider());
        $container->register(new SolrServiceProvider());
        $container->register(new ThemeServiceProvider());
        $container->register(new TranslationServiceProvider());
        $container->register(new TwigServiceProvider());
        $container->register(new LoggerServiceProvider());
        $container->register(new BagsServiceProvider());
        $container->register(new FactoryServiceProvider());
        if ($this->isDebug()) {
            $container->register(new DebugServiceProvider());
        }

        try {
            /*
             * Load additional service providers
             */
            if (isset($container['config']['additionalServiceProviders'])) {
                foreach ($container['config']['additionalServiceProviders'] as $providerClass) {
                    $container->register(new $providerClass());
                }
            }
        } catch (NoConfigurationFoundException $e) {
            // Do nothing if no configuration file is found.
        }

        $container['stopwatch']->stop('registerServices');
    }

    /**
     * Handles a Roadiz master Request and transforms it into a Response.
     *
     * Roadiz default handling is by-passed for assets serving.
     *
     * @param Request $request
     * @param int $type
     * @param bool $catch
     * @return Response
     */
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        if (false === $this->booted) {
            $this->boot();
        }

        /*
         * Bypass Roadiz kernel handling to directly serve images assets
         * -----
         * this is useful in preview mode in order to allow at least assets
         * to be viewed (e.g. PDF generation which loads images in preview mode)
         */
        if (0 === strpos($request->getPathInfo(), '/assets') &&
            preg_match('#^/assets/(?P<queryString>[a-zA-Z:0-9\\-]+)/(?P<filename>[a-zA-Z0-9\\-_\\./]+)$#s', $request->getPathInfo(), $matches)
        ) {
            $ctrl = new AssetsController();
            $ctrl->setContainer($this->getContainer());
            $response = $ctrl->interventionRequestAction($request, $matches['queryString'], $matches['filename']);
            $response->headers->add(['X-ByPass-Kernel' => true]);
            $response->prepare($request);

            return $response;
        }

        $this->container['request'] = $request;
        $this->container['requestContext']->fromRequest($request);
        $this->initEvents();

        return $this->container['httpKernel']->handle($request, $type, $catch);
    }

    /**
     * Register KernelEvents subscribers.
     */
    public function initEvents()
    {
        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->container['dispatcher'];

        $dispatcher->addSubscriber($this->container['routeListener']);
        $dispatcher->addSubscriber($this->container['firewall']);
        $dispatcher->addSubscriber(new SaveSessionListener());
        $dispatcher->addSubscriber(new ResponseListener($this->getCharset()));
        $dispatcher->addSubscriber(new ExceptionSubscriber($this, $this->container['themeResolver'], $this->container['logger'], $this->isDebug()));
        $dispatcher->addSubscriber(new ThemesSubscriber($this, $this->container['stopwatch']));
        $dispatcher->addSubscriber(new ControllerMatchedSubscriber($this, $this->container['stopwatch']));

        if (!$this->isInstallMode()) {
            $dispatcher->addSubscriber(new LocaleSubscriber($this, $this->container['stopwatch']));
            $dispatcher->addSubscriber(new UserLocaleSubscriber($this->container));

            if ($this->isPreview()) {
                $dispatcher->addSubscriber(new PreviewModeSubscriber($this->container));
                $dispatcher->addSubscriber(new PreviewBarSubscriber($this->container));
            }
        }

        $dispatcher->addSubscriber(new MaintenanceModeSubscriber($this->container));
        $dispatcher->addSubscriber(new SignatureListener(static::$cmsVersion, $this->isDebug()));

        /*
         * If debug, alter HTML responses to append Debug panel to view
         */
        if (!$this->isInstallMode() && $this->isDebug()) {
            $dispatcher->addSubscriber(new DebugBarSubscriber($this->container));
            $dispatcher->addSubscriber(new PimpleDumperSubscriber($this->container));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getEnvironment()
    {
        return $this->environment;
    }
    /**
     * {@inheritdoc}
     */
    public function isDebug()
    {
        return $this->debug;
    }

    /**
     * @return boolean
     */
    public function isInstallMode()
    {
        return $this->environment == 'install';
    }

    /**
     * @return boolean
     */
    public function isPreview()
    {
        return $this->preview;
    }

    /**
     * @return boolean
     */
    public function isDevMode()
    {
        return $this->environment == 'dev';
    }

    /**
     * @return boolean
     */
    public function isProdMode()
    {
        return $this->environment == 'prod';
    }

    /**
     * {@inheritdoc}
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get($serviceName)
    {
        return $this->container->offsetGet($serviceName);
    }

    /**
     * {@inheritdoc}
     */
    public function has($serviceName)
    {
        return $this->container->offsetExists($serviceName);
    }

    /**
     * {@inheritdoc}
     */
    public function terminate(Request $request, Response $response)
    {
        if (false === $this->booted) {
            return;
        }
        if ($this->container['httpKernel'] instanceof TerminableInterface) {
            $this->container['httpKernel']->terminate($request, $response);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function shutdown()
    {
        if (false === $this->booted) {
            return;
        }

        $this->booted = false;
        $this->container = null;
    }

    /**
     * Gets a HTTP kernel from the container.
     *
     * @return HttpKernel
     */
    protected function getHttpKernel()
    {
        return $this->container['httpKernel'];
    }

    /**
     * {@inheritdoc}
     */
    public function getBundles()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getBundle($name, $first = true)
    {
        return false;
    }
    /**
     * {@inheritdoc}
     */
    public function locateResource($name, $dir = null, $first = true)
    {
        return false;
    }
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'roadiz';
    }

    /**
     * {@inheritdoc}
     */
    public function getRootDir()
    {
        return ROADIZ_ROOT;
    }

    /**
     * @return string Return web public root.
     */
    public function getPublicDir()
    {
        return ROADIZ_ROOT;
    }

    /**
     * @return string Return Composer vendor root folder.
     */
    public function getVendorDir()
    {
        return $this->getRootDir() . '/vendor';
    }

    /**
     * {@inheritdoc}
     */
    public function getStartTime()
    {
        return $this->debug ? $this->startTime : -INF;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir()
    {
        if ($this->isPreview()) {
            return $this->getRootDir() . '/cache/' . $this->environment . '_preview';
        }
        return $this->getRootDir() . '/cache/' . $this->environment;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDir()
    {
        return $this->getRootDir() . '/logs';
    }

    /**
     * {@inheritdoc}
     */
    public function getCharset()
    {
        return 'UTF-8';
    }

    /**
     * Returns an array of bundles to register.
     *
     * @return BundleInterface[] An array of bundle instances.
     */
    public function registerBundles()
    {
        return [];
    }

    /**
     * Loads the container configuration.
     *
     * @param LoaderInterface $loader A LoaderInterface instance
     * @return bool
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        return false;
    }

    /**
     * @deprecated since version 2.6, to be removed in 3.0.
     * @param string $class
     * @return bool
     */
    public function isClassInActiveBundle($class)
    {
        return false;
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize(array($this->environment, $this->debug, $this->preview));
    }

    /**
     * @param string $data
     */
    public function unserialize($data)
    {
        list($environment, $debug, $preview) = unserialize($data);
        $this->__construct($environment, $debug, $preview);
    }

    /**
     * {@inheritdoc}
     */
    public function getPublicFilesPath()
    {
        return $this->getPublicDir() . $this->getPublicFilesBasePath();
    }

    /**
     * {@inheritdoc}
     */
    public function getPublicFilesBasePath()
    {
        return '/files';
    }

    /**
     * @inheritDoc
     */
    public function getPublicCachePath()
    {
        return $this->getPublicDir() . $this->getPublicCacheBasePath();
    }

    /**
     * @inheritDoc
     */
    public function getPublicCacheBasePath()
    {
        return '/assets';
    }

    /**
     * {@inheritdoc}
     */
    public function getPrivateFilesPath()
    {
        return $this->getRootDir() . $this->getPrivateFilesBasePath();
    }

    /**
     * {@inheritdoc}
     */
    public function getPrivateFilesBasePath()
    {
        return '/files/private';
    }

    /**
     * {@inheritdoc}
     */
    public function getFontsFilesPath()
    {
        return $this->getRootDir() . $this->getFontsFilesBasePath();
    }

    /**
     * {@inheritdoc}
     */
    public function getFontsFilesBasePath()
    {
        return '/files/fonts';
    }
}
