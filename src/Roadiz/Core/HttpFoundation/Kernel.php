<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
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
namespace RZ\Roadiz\Core\HttpFoundation;

use Symfony\Component\HttpKernel\HttpKernel;
use Pimple\Container;

/**
*
*/
class Kernel extends HttpKernel implements ServiceProviderInterface
{
    const CMS_VERSION = 'alpha';
    const SECURITY_DOMAIN = 'roadiz_domain';
    const INSTALL_CLASSNAME = '\\Themes\\Install\\InstallApp';

    public static $cmsBuild = null;
    public static $cmsVersion = "0.10.1";
    protected static $instance = null;

    public $container = null;

    protected $environment;
    protected $debug;

    /**
     * @param string $environment
     * @param boolean $debug
     */
    final private function __construct($environment, $debug)
    {
        $this->container = new Container();

        $this->environment = $environment;
        $this->debug = (boolean) $debug;
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
            return new DebugPanel($c);
        };

        $container['dispatcher'] = function ($c) {
            return new EventDispatcher();
        };

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

        $this->dispatcher = $this->container['dispatcher'];
        $this->resolver = $this->container['resolver'];
        $this->requestStack = $this->container['requestStack'];
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
}
