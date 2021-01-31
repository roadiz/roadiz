<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Routing;

use RZ\Roadiz\Core\Bags\Settings;
use RZ\Roadiz\Preview\PreviewResolverInterface;
use RZ\Roadiz\Utils\Theme\ThemeResolverInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * @package RZ\Roadiz\Core\Routing
 * @deprecated Convert logic into Symfony\Cmf\Component\Routing\RouteProviderInterface
 */
class RoadizRouteCollection extends DeferredRouteCollection
{
    /**
     * @var Stopwatch
     */
    protected $stopwatch;
    /**
     * @var ThemeResolverInterface
     */
    protected $themeResolver;
    /**
     * @var Settings
     */
    protected $settingsBag;
    /**
     * @var PreviewResolverInterface
     */
    protected $previewResolver;

    protected bool $locked = false;

    /**
     * @param ThemeResolverInterface $themeResolver
     * @param Settings $settingsBag
     * @param PreviewResolverInterface $previewResolver
     * @param Stopwatch|null $stopwatch
     */
    public function __construct(
        ThemeResolverInterface $themeResolver,
        Settings $settingsBag,
        PreviewResolverInterface $previewResolver,
        Stopwatch $stopwatch = null
    ) {
        $this->stopwatch = $stopwatch;
        $this->themeResolver = $themeResolver;
        $this->settingsBag = $settingsBag;
        $this->previewResolver = $previewResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function parseResources(): void
    {
        if (null !== $this->stopwatch) {
            $this->stopwatch->start('routeCollection');
        }

        $this->getResources();
        if (!$this->locked) {
            /*
             * Adding Backend routes
             */
            $this->addBackendCollection();

            /*
             * Add Assets controller routes
             */
            $this->addDomainAwareCollection();

            /*
             * Add Frontend routes
             *
             * return 'RZ\Roadiz\CMS\Controllers\FrontendController';
             */
            $this->addThemesCollections();
            $this->locked = true;
        }
        if (null !== $this->stopwatch) {
            $this->stopwatch->stop('routeCollection');
        }
    }

    protected function addDomainAwareCollection(): void
    {
        /*
         * Add Assets controller routes
         */
        $cmsResourcesDir = dirname(__DIR__) . '/../CMS/Resources';
        $locator = new FileLocator([
            $cmsResourcesDir,
            $cmsResourcesDir . '/routing',
            $cmsResourcesDir . '/config',
        ]);
        $loader = new YamlFileLoader($locator);
        $assets = $loader->load('routes.yml');
        $staticDomain = $this->settingsBag->get('static_domain_name');
        if (false === $this->previewResolver->isPreview() &&
            false !== $staticDomain &&
            '' != $staticDomain) {
            /*
             * Only use CDN if no preview mode and CDN domain is well set
             * Remove protocol (https, http and protocol-less) information from domain.
             */
            $host = parse_url($staticDomain, PHP_URL_HOST);
            if (false !== $host && null !== $host) {
                $assets->setHost($host);
            } else {
                $assets->setHost($staticDomain);
            }
            /*
             * ~~Use same scheme as static domain.~~
             *
             * DO NOT use setSchemes method as it need a special UrlMatcher
             * only available on Symfony full-stack
             */
        }
        $this->addCollection($assets);
    }

    protected function addBackendCollection(): void
    {
        $class = $this->themeResolver->getBackendClassName();
        if (class_exists($class)) {
            $collection = call_user_func([$class, 'getRoutes']);
            if (null !== $collection) {
                $this->addCollection($collection);
            }
        } else {
            throw new \RuntimeException("Class “" . $class . "” does not exist.", 1);
        }
    }

    protected function addThemesCollections(): void
    {
        $frontendThemes = $this->themeResolver->getFrontendThemes();
        foreach ($frontendThemes as $theme) {
            $feClass = $theme->getClassName();
            /** @var RouteCollection $feCollection */
            $feCollection = call_user_func([$feClass, 'getRoutes']);
            /** @var RouteCollection $feBackendCollection */
            $feBackendCollection = call_user_func([$feClass, 'getBackendRoutes']);

            if ($feCollection !== null) {
                // set host pattern if defined
                if ($theme->getHostname() != '*' &&
                    $theme->getHostname() != '') {
                    $feCollection->setHost($theme->getHostname());
                }
                /*
                 * Add a global prefix on theme static routes
                 */
                if ($theme->getRoutePrefix() != '') {
                    $feCollection->addPrefix($theme->getRoutePrefix());
                }
                $this->addCollection($feCollection);
            }

            if ($feBackendCollection !== null) {
                /*
                 * Do not prefix or hostname admin routes.
                 */
                $this->addCollection($feBackendCollection);
            }
        }
    }
}
