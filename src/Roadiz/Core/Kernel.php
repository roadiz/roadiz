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
use RZ\Roadiz\Core\Events\ControllerMatchedSubscriber;
use RZ\Roadiz\Core\Events\ExceptionSubscriber;
use RZ\Roadiz\Core\Events\LocaleSubscriber;
use RZ\Roadiz\Core\Events\MaintenanceModeSubscriber;
use RZ\Roadiz\Core\Events\PreviewModeSubscriber;
use RZ\Roadiz\Core\Events\ThemesSubscriber;
use RZ\Roadiz\Utils\DebugPanel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 *
 */
class Kernel implements ServiceProviderInterface, KernelInterface, TerminableInterface
{
    const CMS_VERSION = 'alpha';
    const SECURITY_DOMAIN = 'roadiz_domain';
    const INSTALL_CLASSNAME = '\\Themes\\Install\\InstallApp';

    public static $cmsBuild = null;
    public static $cmsVersion = "0.14.1";
    protected static $instance = null;

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

        /*
         * Register current Kernel as a service provider.
         */
        $this->container = new Container();
        $this->container->register($this);

        $this->booted = true;

    }

    /**
     * Register every services needed by Roadiz CMS.
     *
     * @param Container $container
     */
    public function register(Container $container)
    {
        $container['stopwatch'] = function () {
            return new Stopwatch();
        };

        $container['debugPanel'] = function ($c) {
            return new DebugPanel($c);
        };

        $container['dispatcher'] = function () {
            return new EventDispatcher();
        };

        $container['kernel'] = $this;
        $container['stopwatch']->openSection();
        $container['stopwatch']->start('registerServices');

        $container->register(new \RZ\Roadiz\Core\Services\YamlConfigurationServiceProvider());
        $container->register(new \RZ\Roadiz\Core\Services\AssetsServiceProvider());
        $container->register(new \RZ\Roadiz\Core\Services\BackofficeServiceProvider());
        $container->register(new \RZ\Roadiz\Core\Services\DoctrineServiceProvider());
        $container->register(new \RZ\Roadiz\Core\Services\EmbedDocumentsServiceProvider());
        $container->register(new \RZ\Roadiz\Core\Services\EntityApiServiceProvider());
        $container->register(new \RZ\Roadiz\Core\Services\FormServiceProvider());
        $container->register(new \RZ\Roadiz\Core\Services\MailerServiceProvider());
        $container->register(new \RZ\Roadiz\Core\Services\RoutingServiceProvider());
        $container->register(new \RZ\Roadiz\Core\Services\SecurityServiceProvider());
        $container->register(new \RZ\Roadiz\Core\Services\SolrServiceProvider());
        $container->register(new \RZ\Roadiz\Core\Services\ThemeServiceProvider());
        $container->register(new \RZ\Roadiz\Core\Services\TranslationServiceProvider());
        $container->register(new \RZ\Roadiz\Core\Services\TwigServiceProvider());

        /*
         * Load additional service providers
         */
        if (isset($container['config']['additionalServiceProviders'])) {
            foreach ($container['config']['additionalServiceProviders'] as $providerClass) {
                $container->register(new $providerClass());
            }
        }
        $container['stopwatch']->stop('registerServices');
    }

    /**
     * {@inheritdoc}
     *
     */
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        if (false === $this->booted) {
            $this->boot();
        }

        /*
         * Define a request wide timezone
         */
        if (!empty($this->container['config']["timezone"])) {
            date_default_timezone_set($this->container['config']["timezone"]);
        } else {
            date_default_timezone_set("Europe/Paris");
        }

        $this->container['request'] = $request;

        $this->initEvents();

        return $this->container['httpKernel']->handle($request, $type, $catch);
    }

    /**
     * Register KernelEvents subscribers.
     */
    protected function initEvents()
    {
        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->container['dispatcher'];

        $dispatcher->addSubscriber($this->container['routeListener']);
        $dispatcher->addSubscriber($this->container['firewall']);
        $dispatcher->addSubscriber(new ExceptionSubscriber($this->container['logger'], $this->isDebug()));
        $dispatcher->addSubscriber(new ThemesSubscriber($this, $this->container['stopwatch']));
        $dispatcher->addSubscriber(new ControllerMatchedSubscriber($this, $this->container['stopwatch']));

        if (!$this->isInstallMode()) {
            $dispatcher->addSubscriber(new LocaleSubscriber($this, $this->container['stopwatch']));

            if ($this->isPreview()) {
                $dispatcher->addSubscriber(new PreviewModeSubscriber($this->container));
            }
        }

        $dispatcher->addSubscriber(new MaintenanceModeSubscriber($this->container));

        /*
         * If debug, alter HTML responses to append Debug panel to view
         */
        if ($this->isDebug()) {
            $dispatcher->addSubscriber($this->container['debugPanel']);
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
     * Return unique instance of Kernel.
     *
     * @param string $environment
     * @param bool $debug
     * @param bool $preview
     *
     * @return Kernel
     */
    public static function getInstance($environment = 'prod', $debug = false, $preview = false)
    {
        if (static::$instance === null) {
            static::$instance = new Kernel($environment, $debug, $preview);
        }

        return static::$instance;
    }

    /**
     * @return \Pimple\Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * {@inheritdoc}
     *
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
     *
     * @api
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
     *
     */
    public function getBundles()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     *
     */
    public function getBundle($name, $first = true)
    {
        return false;
    }
    /**
     * {@inheritdoc}
     *
     */
    public function locateResource($name, $dir = null, $first = true)
    {
        return false;
    }
    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function getName()
    {
        return 'roadiz';
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function getRootDir()
    {
        return ROADIZ_ROOT;
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function getStartTime()
    {
        return $this->debug ? $this->startTime : -INF;
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function getCacheDir()
    {
        return ROADIZ_ROOT . '/cache/' . $this->environment;
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function getLogDir()
    {
        return ROADIZ_ROOT . '/logs';
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function getCharset()
    {
        return 'UTF-8';
    }

    /**
     * Returns an array of bundles to register.
     *
     * @return BundleInterface[] An array of bundle instances.
     *
     */
    public function registerBundles()
    {
        return [];
    }

    /**
     * Loads the container configuration.
     *
     * @param LoaderInterface $loader A LoaderInterface instance
     *
     * @return bool
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        return false;
    }

    /**
     *
     * @deprecated since version 2.6, to be removed in 3.0.
     * @param string $class
     *
     * @return bool
     */
    public function isClassInActiveBundle($class)
    {
        return false;
    }

    public function serialize()
    {
        return serialize(array($this->environment, $this->debug, $this->preview));
    }

    public function unserialize($data)
    {
        list($environment, $debug, $preview) = unserialize($data);
        $this->__construct($environment, $debug, $preview);
    }
}
