<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Attribute\AttributesServiceProvider;
use RZ\Roadiz\CMS\Controllers\AssetsController;
use RZ\Roadiz\Core\Exceptions\NoConfigurationFoundException;
use RZ\Roadiz\Core\Models\FileAwareInterface;
use RZ\Roadiz\Core\Services\AssetsServiceProvider;
use RZ\Roadiz\Core\Services\BackofficeServiceProvider;
use RZ\Roadiz\Core\Services\BagsServiceProvider;
use RZ\Roadiz\Core\Services\ConsoleServiceProvider;
use RZ\Roadiz\Core\Services\CryptoServiceProvider;
use RZ\Roadiz\Core\Services\DebugServiceProvider;
use RZ\Roadiz\Core\Services\DoctrineFiltersServiceProvider;
use RZ\Roadiz\Core\Services\DoctrineServiceProvider;
use RZ\Roadiz\Core\Services\EmbedDocumentsServiceProvider;
use RZ\Roadiz\Core\Services\EntityApiServiceProvider;
use RZ\Roadiz\Core\Services\EventDispatcherServiceProvider;
use RZ\Roadiz\Core\Services\FactoryServiceProvider;
use RZ\Roadiz\Core\Services\FormServiceProvider;
use RZ\Roadiz\Core\Services\ImporterServiceProvider;
use RZ\Roadiz\Core\Services\LoggerServiceProvider;
use RZ\Roadiz\Core\Services\MailerServiceProvider;
use RZ\Roadiz\Core\Services\NodeServiceProvider;
use RZ\Roadiz\Core\Services\RoutingServiceProvider;
use RZ\Roadiz\Core\Services\SecurityServiceProvider;
use RZ\Roadiz\Core\Services\SerializationServiceProvider;
use RZ\Roadiz\Core\Services\SolrServiceProvider;
use RZ\Roadiz\Core\Services\ThemeServiceProvider;
use RZ\Roadiz\Core\Services\TwigServiceProvider;
use RZ\Roadiz\Core\Services\YamlConfigurationServiceProvider;
use RZ\Roadiz\Core\Viewers\ExceptionViewer;
use RZ\Roadiz\Documentation\DocumentationServiceProvider;
use RZ\Roadiz\EntityGenerator\EntityGeneratorServiceProvider;
use RZ\Roadiz\Markdown\Services\MarkdownServiceProvider;
use RZ\Roadiz\OpenId\OpenIdServiceProvider;
use RZ\Roadiz\Preview\PreviewServiceProvider;
use RZ\Roadiz\Translation\Services\TranslationServiceProvider;
use RZ\Roadiz\Utils\DebugBar\NullStopwatch;
use RZ\Roadiz\Utils\Services\UtilsServiceProvider;
use RZ\Roadiz\Workflow\WorkflowServiceProvider;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\RebootableInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Themes\Install\InstallApp;
use Themes\Rozier\Services\RozierServiceProvider;

/**
 * @package RZ\Roadiz\Core
 */
class Kernel implements ServiceProviderInterface, KernelInterface, RebootableInterface, TerminableInterface, ContainerAwareInterface, FileAwareInterface
{
    use ContainerAwareTrait;

    const CMS_VERSION = 'master';
    const SECURITY_DOMAIN = 'roadiz_domain';
    const INSTALL_CLASSNAME = InstallApp::class;
    public static ?string $cmsBuild = null;
    public static string $cmsVersion = "1.6.39";
    protected string $environment;
    protected bool $debug;
    /**
     * @var bool
     * @deprecated Use request-time preview
     */
    protected bool $preview = false;
    protected bool $booted = false;
    protected ?string $name = null;
    protected ?string $rootDir = null;
    protected ?string $warmupDir = null;
    protected ?string $projectDir = null;
    protected float $startTime;
    protected int $requestStackSize = 0;

    /**
     * @param string $environment
     * @param bool $debug
     * @param bool $preview
     */
    public function __construct(string $environment, bool $debug, bool $preview = false)
    {
        $this->environment = strtolower((string) $environment);
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
        @ini_set(
            'session.name',
            (string) $this->container['config']["security"]["session_name"]
        );
        @ini_set(
            'session.cookie_secure',
            (string) $this->container['config']["security"]["session_cookie_secure"]
        );
        @ini_set(
            'session.cookie_httponly',
            (string) $this->container['config']["security"]["session_cookie_httponly"]
        );
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
        $stopWatch->start('kernel.registerServices');

        $container->register(new EventDispatcherServiceProvider());
        $container->register(new YamlConfigurationServiceProvider());
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
        $container->register(new CryptoServiceProvider());
        $container->register(new NodeServiceProvider());
        $container->register(new MarkdownServiceProvider());
        $container->register(new OpenIdServiceProvider());
        $container->register(new RozierServiceProvider());
        $container->register(new PreviewServiceProvider());
        $container->register(new EntityGeneratorServiceProvider());
        $container->register(new DocumentationServiceProvider());

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

        $stopWatch->stop('kernel.registerServices');
    }

    /**
     * Handles a Roadiz master Request and transforms it into a Response.
     *
     * Roadiz default handling is by-passed for assets serving.
     *
     * @param Request $request
     * @param int     $type
     * @param bool    $catch
     *
     * @return Response
     * @throws \Exception
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
                $ctrl = $this->get(AssetsController::class);
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
        return $this->environment === 'install';
    }

    /**
     * @return boolean
     * @deprecated Use request-time preview
     */
    public function isPreview()
    {
        return $this->preview;
    }

    /**
     * @param bool $preview
     * @return Kernel
     */
    public function setPreview(bool $preview): Kernel
    {
        $this->preview = $preview;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isDevMode()
    {
        return $this->environment === 'dev';
    }

    /**
     * @return boolean
     */
    public function isProdMode()
    {
        return $this->environment === 'prod';
    }

    /**
     * {@inheritdoc}
     */
    public function terminate(Request $request, Response $response)
    {
        if (false === $this->booted) {
            return;
        }
        if ($this->getHttpKernel() instanceof TerminableInterface) {
            $this->getHttpKernel()->terminate($request, $response);
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

            if (!file_exists($dir = $r->getFileName())) {
                throw new \LogicException(sprintf('Cannot auto-detect project dir for kernel of class "%s".', $r->name));
            }

            $dir = $rootDir = \dirname($dir);
            while (!file_exists($dir.'/composer.json')) {
                if ($dir === \dirname($dir)) {
                    return $this->projectDir = $rootDir;
                }
                $dir = \dirname($dir);
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
    public function getPublicFilesPath(): string
    {
        return $this->getPublicDir() . $this->getPublicFilesBasePath();
    }

    /**
     * {@inheritdoc}
     */
    public function getPublicFilesBasePath(): string
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
    public function getPrivateFilesPath(): string
    {
        return $this->getProjectDir() . $this->getPrivateFilesBasePath();
    }

    /**
     * {@inheritdoc}
     */
    public function getPrivateFilesBasePath(): string
    {
        return '/files/private';
    }

    /**
     * {@inheritdoc}
     */
    public function getFontsFilesPath(): string
    {
        return $this->getRootDir() . $this->getFontsFilesBasePath();
    }

    /**
     * {@inheritdoc}
     */
    public function getFontsFilesBasePath(): string
    {
        return '/files/fonts';
    }
}
