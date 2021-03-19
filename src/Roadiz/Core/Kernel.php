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
use RZ\Roadiz\Attribute\AttributesServiceProvider;
use RZ\Roadiz\CMS\Controllers\AssetsController;
use RZ\Roadiz\Core\Events\ControllerMatchedSubscriber;
use RZ\Roadiz\Core\Events\DebugBarSubscriber;
use RZ\Roadiz\Core\Events\ExceptionSubscriber;
use RZ\Roadiz\Core\Events\LocaleSubscriber;
use RZ\Roadiz\Core\Events\MaintenanceModeSubscriber;
use RZ\Roadiz\Core\Events\NodeNameSubscriber;
use RZ\Roadiz\Core\Events\NodeSourcePathSubscriber;
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
use RZ\Roadiz\Core\Services\ConsoleServiceProvider;
use RZ\Roadiz\Core\Services\DebugServiceProvider;
use RZ\Roadiz\Core\Services\DoctrineFiltersServiceProvider;
use RZ\Roadiz\Core\Services\DoctrineServiceProvider;
use RZ\Roadiz\Core\Services\EmbedDocumentsServiceProvider;
use RZ\Roadiz\Core\Services\EntityApiServiceProvider;
use RZ\Roadiz\Core\Services\FactoryServiceProvider;
use RZ\Roadiz\Core\Services\FormServiceProvider;
use RZ\Roadiz\Core\Services\ImporterServiceProvider;
use RZ\Roadiz\Core\Services\LoggerServiceProvider;
use RZ\Roadiz\Core\Services\MailerServiceProvider;
use RZ\Roadiz\Core\Services\RoutingServiceProvider;
use RZ\Roadiz\Core\Services\SecurityServiceProvider;
use RZ\Roadiz\Core\Services\SerializationServiceProvider;
use RZ\Roadiz\Core\Services\SolrServiceProvider;
use RZ\Roadiz\Core\Services\ThemeServiceProvider;
use RZ\Roadiz\Core\Services\TranslationServiceProvider;
use RZ\Roadiz\Core\Services\TwigServiceProvider;
use RZ\Roadiz\Core\Services\YamlConfigurationServiceProvider;
use RZ\Roadiz\Core\Viewers\ExceptionViewer;
use RZ\Roadiz\Utils\Clearer\EventListener\AppCacheEventSubscriber;
use RZ\Roadiz\Utils\Clearer\EventListener\AssetsCacheEventSubscriber;
use RZ\Roadiz\Utils\Clearer\EventListener\ConfigurationCacheEventSubscriber;
use RZ\Roadiz\Utils\Clearer\EventListener\DoctrineCacheEventSubscriber;
use RZ\Roadiz\Utils\Clearer\EventListener\NodesSourcesUrlsCacheEventSubscriber;
use RZ\Roadiz\Utils\Clearer\EventListener\OPCacheEventSubscriber;
use RZ\Roadiz\Utils\Clearer\EventListener\ReverseProxyCacheEventSubscriber;
use RZ\Roadiz\Utils\Clearer\EventListener\RoutingCacheEventSubscriber;
use RZ\Roadiz\Utils\Clearer\EventListener\TemplatesCacheEventSubscriber;
use RZ\Roadiz\Utils\Clearer\EventListener\TranslationsCacheEventSubscriber;
use RZ\Roadiz\Utils\DebugBar\NullStopwatch;
use RZ\Roadiz\Utils\Services\UtilsServiceProvider;
use RZ\Roadiz\Workflow\WorkflowServiceProvider;
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
use Symfony\Component\HttpKernel\RebootableInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Themes\Install\InstallApp;
use Themes\Rozier\Events\DocumentSizeSubscriber;
use Themes\Rozier\Events\ExifDocumentSubscriber;
use Themes\Rozier\Events\NodeDuplicationSubscriber;
use Themes\Rozier\Events\NodesSourcesUniversalSubscriber;
use Themes\Rozier\Events\NodesSourcesUrlSubscriber;
use Themes\Rozier\Events\RawDocumentsSubscriber;
use Themes\Rozier\Events\SolariumSubscriber;
use Themes\Rozier\Events\SvgDocumentSubscriber;
use Themes\Rozier\Events\TranslationSubscriber;

/**
 *
 */
class Kernel implements ServiceProviderInterface, KernelInterface, RebootableInterface, TerminableInterface, ContainerAwareInterface, FileAwareInterface
{
    use ContainerAwareTrait;

    const CMS_VERSION = 'master';
    const SECURITY_DOMAIN = 'roadiz_domain';
    const INSTALL_CLASSNAME = InstallApp::class;
    public static $cmsBuild = null;
    public static $cmsVersion = "1.2.33";

    protected $environment;
    protected $debug;
    protected $preview;
    protected $booted = false;
    protected $name;
    protected $rootDir;
    protected $startTime;

    private $warmupDir;
    private $projectDir;
    private $requestStackSize = 0;

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
    }

    public function __clone()
    {
        $this->booted = false;
        $this->container = null;
        $this->requestStackSize = 0;
    }

    /**
     * Boots the current kernel.
     */
    public function boot()
    {
        if (true === $this->booted) {
            return;
        }

        if ($this->debug) {
            $this->startTime = microtime(true);
        }
        if ($this->debug && !isset($_ENV['SHELL_VERBOSITY']) && !isset($_SERVER['SHELL_VERBOSITY'])) {
            putenv('SHELL_VERBOSITY=3');
            $_ENV['SHELL_VERBOSITY'] = 3;
            $_SERVER['SHELL_VERBOSITY'] = 3;
        }

        try {
            $this->initializeContainer();
            $this->initEvents();
            $this->booted = true;
        } catch (InvalidConfigurationException $e) {
            $view = new ExceptionViewer();
            $response = $view->getResponse($e, Request::createFromGlobals(), $this->isDebug());
            $response->send();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function reboot($warmupDir)
    {
        $this->shutdown();
        $this->warmupDir = $warmupDir;
        $this->boot();
    }

    /**
     *
     */
    public function initializeContainer()
    {
        foreach (['cache' => $this->warmupDir ?: $this->getCacheDir(), 'logs' => $this->getLogDir()] as $name => $dir) {
            if (!is_dir($dir)) {
                if (false === @mkdir($dir, 0777, true) && !is_dir($dir)) {
                    throw new \RuntimeException(sprintf("Unable to create the %s directory (%s)\n", $name, $dir));
                }
            } elseif (!is_writable($dir)) {
                throw new \RuntimeException(sprintf("Unable to write in the %s directory (%s)\n", $name, $dir));
            }
        }
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

        $container['kernel'] = $this;
        /** @var Stopwatch $stopWatch */
        $stopWatch = $container['stopwatch'];
        $stopWatch->openSection();
        $stopWatch->start('registerServices');

        $container->register(new YamlConfigurationServiceProvider());

        $container['dispatcher'] = function (Container $c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            $dispatcher = new EventDispatcher();
            $dispatcher->addSubscriber(new SaveSessionListener());
            $dispatcher->addSubscriber(new AppCacheEventSubscriber());
            $dispatcher->addSubscriber(new AssetsCacheEventSubscriber());
            $dispatcher->addSubscriber(new ConfigurationCacheEventSubscriber());
            $dispatcher->addSubscriber(new DoctrineCacheEventSubscriber());
            $dispatcher->addSubscriber(new NodesSourcesUrlsCacheEventSubscriber());
            $dispatcher->addSubscriber(new OPCacheEventSubscriber());
            $dispatcher->addSubscriber(new RoutingCacheEventSubscriber());
            $dispatcher->addSubscriber(new TemplatesCacheEventSubscriber());
            $dispatcher->addSubscriber(new TranslationsCacheEventSubscriber());
            $dispatcher->addSubscriber(new ReverseProxyCacheEventSubscriber($c));
            $dispatcher->addSubscriber(new ResponseListener($kernel->getCharset()));
            $dispatcher->addSubscriber(new MaintenanceModeSubscriber($c));
            $dispatcher->addSubscriber(new NodeSourcePathSubscriber());
            $dispatcher->addSubscriber(new NodeNameSubscriber($c['logger'], $c['utils.nodeNameChecker']));
            $dispatcher->addSubscriber(new SignatureListener($kernel::$cmsVersion, $kernel->isDebug()));
            if (!$kernel->isDebug()) {
                /**
                 * Do not prevent Symfony Debug tool to perform
                 * in debug mode.
                 */
                $dispatcher->addSubscriber(new ExceptionSubscriber(
                    $c,
                    $c['themeResolver'],
                    $c['logger'],
                    $kernel->isDebug()
                ));
            }
            $dispatcher->addSubscriber(new ThemesSubscriber($kernel, $c['stopwatch']));
            $dispatcher->addSubscriber(new ControllerMatchedSubscriber($kernel, $c['stopwatch']));

            if (!$kernel->isInstallMode()) {
                $dispatcher->addSubscriber(new LocaleSubscriber($kernel, $c['stopwatch']));
                $dispatcher->addSubscriber(new UserLocaleSubscriber($c));

                /*
                 * Add custom event subscriber to empty NS Url cache
                 */
                $dispatcher->addSubscriber(
                    new NodesSourcesUrlSubscriber($c['nodesSourcesUrlCacheProvider'])
                );
                /*
                 * Add custom event subscriber to Translation result cache
                 */
                $dispatcher->addSubscriber(
                    new TranslationSubscriber($c['em']->getConfiguration()->getResultCacheImpl())
                );
                /*
                 * Add custom event subscriber to manage universal node-type fields
                 */
                $dispatcher->addSubscriber(
                    new NodesSourcesUniversalSubscriber($c['em'], $c['utils.universalDataDuplicator'])
                );

                /*
                 * Add custom event subscriber to manage Svg document sanitizing
                 */
                $dispatcher->addSubscriber(
                    new SvgDocumentSubscriber(
                        $c['assetPackages'],
                        $c['logger']
                    )
                );
                /*
                 * Add custom event subscriber to manage image document size
                 */
                $dispatcher->addSubscriber(
                    new DocumentSizeSubscriber(
                        $c['assetPackages'],
                        $c['logger']
                    )
                );

                /*
                 * Add custom event subscriber to manage document EXIF
                 */
                $dispatcher->addSubscriber(
                    new ExifDocumentSubscriber(
                        $c['em'],
                        $c['assetPackages'],
                        $c['logger']
                    )
                );

                /*
                 * Add custom event subscriber to create a downscaled version for HD images.
                 */
                $dispatcher->addSubscriber(
                    new RawDocumentsSubscriber(
                        $c['em'],
                        $c['assetPackages'],
                        $c['logger'],
                        $c['config']['assetsProcessing']['driver'],
                        $c['config']['assetsProcessing']['maxPixelSize']
                    )
                );


                if ($kernel->isPreview()) {
                    $dispatcher->addSubscriber(new PreviewModeSubscriber($c));
                    $dispatcher->addSubscriber(new PreviewBarSubscriber($c));
                }
            }
            /*
             * If debug, alter HTML responses to append Debug panel to view
             */
            if (!$kernel->isInstallMode() && $kernel->isDebug()) {
                $dispatcher->addSubscriber(new DebugBarSubscriber($c));
            }

            return $dispatcher;
        };

        $container->register(new ConsoleServiceProvider());
        $container->register(new AssetsServiceProvider());
        $container->register(new BackofficeServiceProvider());
        $container->register(new DoctrineServiceProvider());
        $container->register(new DoctrineFiltersServiceProvider());
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
        $container->register(new ImporterServiceProvider());
        $container->register(new WorkflowServiceProvider());
        $container->register(new SerializationServiceProvider());
        $container->register(new UtilsServiceProvider());
        $container->register(new AttributesServiceProvider());

        if ($this->isDebug()) {
            $container->register(new DebugServiceProvider());
        }

        /**
         * @deprecated Register your custom service providers into AppKernel
         */
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

        $stopWatch->stop('registerServices');
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
        ++$this->requestStackSize;

        try {
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

            return $this->getHttpKernel()->handle($request, $type, $catch);
        } finally {
            --$this->requestStackSize;
        }
    }

    /**
     * Register additional subscribers, especially those which need
     * dispatcher to be woken up.
     */
    protected function initEvents()
    {
        $this->get('dispatcher')->addSubscriber($this->get('firewall'));
        $this->get('dispatcher')->addSubscriber($this->get('routeListener'));
        /*
         * Add custom event subscribers to the general dispatcher.
         *
         * Important: do not check here if Solr respond, not to request
         * solr server at each HTTP request.
         */
        $this->get('dispatcher')->addSubscriber(
            new SolariumSubscriber(
                $this->get('solr'),
                $this->get('dispatcher'),
                $this->get('logger'),
                $this->get('factory.handler')
            )
        );
        /*
         * Add custom event subscriber to manage node duplication
         */
        $this->get('dispatcher')->addSubscriber(
            new NodeDuplicationSubscriber($this->get('em'), $this->get('node.handler'))
        );
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
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getBundle($name, $first = true)
    {
        return [];
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
        if (null === $this->rootDir) {
            $this->rootDir = $this->getProjectDir();
        }
        return $this->rootDir;
    }

    /**
     * Gets the application root dir (path of the project's composer file).
     *
     * @return string The project root dir
     */
    public function getProjectDir()
    {
        if (null === $this->projectDir) {
            $r = new \ReflectionObject($this);
            $dir = $rootDir = dirname($r->getFileName());
            while (!file_exists($dir.'/composer.json')) {
                if ($dir === dirname($dir)) {
                    return $this->projectDir = $rootDir;
                }
                $dir = dirname($dir);
            }
            $this->projectDir = $dir;
        }
        return $this->projectDir;
    }


    /**
     * @return string Return web public root.
     */
    public function getPublicDir()
    {
        return $this->getProjectDir();
    }

    /**
     * @return string Return Composer vendor root folder.
     */
    public function getVendorDir()
    {
        return $this->getProjectDir() . '/vendor';
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
     *
     */
    public function initializeBundles()
    {
        return;
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
     * @return string
     */
    public function serialize()
    {
        return serialize([$this->environment, $this->debug, $this->preview]);
    }

    /**
     * @param string $data
     */
    public function unserialize($data)
    {
        [$environment, $debug, $preview] = unserialize($data);
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
        return $this->getProjectDir() . $this->getPrivateFilesBasePath();
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
