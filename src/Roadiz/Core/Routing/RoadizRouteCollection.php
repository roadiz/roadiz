<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Routing;

use RZ\Roadiz\CMS\Controllers\AssetsController;
use RZ\Roadiz\Core\Bags\Settings;
use RZ\Roadiz\Utils\Theme\ThemeResolverInterface;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Class RoadizRouteCollection.
 *
 * @package RZ\Roadiz\Core\Routing
 * TODO: Convert logic into Symfony\Cmf\Component\Routing\RouteProviderInterface
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
     * @var bool
     */
    private $isPreview;
    /**
     * @var Settings
     */
    private $settingsBag;

    /**
     * @param ThemeResolverInterface $themeResolver
     * @param Settings $settingsBag
     * @param Stopwatch|null $stopwatch
     * @param bool $isPreview
     */
    public function __construct(
        ThemeResolverInterface $themeResolver,
        Settings $settingsBag,
        Stopwatch $stopwatch = null,
        $isPreview = false
    ) {
        $this->stopwatch = $stopwatch;
        $this->themeResolver = $themeResolver;
        $this->isPreview = $isPreview;
        $this->settingsBag = $settingsBag;
    }

    /**
     * {@inheritdoc}
     */
    public function parseResources(): void
    {
        if (null !== $this->stopwatch) {
            $this->stopwatch->start('routeCollection');
        }

        $resources = $this->getResources();
        if (empty($resources)) {
            /*
             * Adding Backend routes
             */
            $this->addBackendCollection();

            /*
             * Add Assets controller routes
             */
            $assets = AssetsController::getRoutes();
            $staticDomain = $this->settingsBag->get('static_domain_name');
            if (false === $this->isPreview &&
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

            /*
             * Add Frontend routes
             *
             * return 'RZ\Roadiz\CMS\Controllers\FrontendController';
             */
            $this->addThemesCollections();
        }
        if (null !== $this->stopwatch) {
            $this->stopwatch->stop('routeCollection');
        }
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
