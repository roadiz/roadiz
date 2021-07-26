<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Services;

use AM\InterventionRequest\Configuration;
use AM\InterventionRequest\InterventionRequest;
use Doctrine\Persistence\ManagerRegistry;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Psr\Log\LoggerInterface;
use RZ\Roadiz\CMS\Controllers\AssetsController;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Document\EventSubscriber\DocumentFilesizeSubscriber;
use RZ\Roadiz\Document\EventSubscriber\DocumentSizeSubscriber;
use RZ\Roadiz\Document\EventSubscriber\DocumentSvgSizeSubscriber;
use RZ\Roadiz\Document\EventSubscriber\ExifDocumentSubscriber;
use RZ\Roadiz\Document\EventSubscriber\ImageColorDocumentSubscriber;
use RZ\Roadiz\Document\EventSubscriber\RawDocumentsSubscriber;
use RZ\Roadiz\Document\EventSubscriber\SvgDocumentSubscriber;
use RZ\Roadiz\Utils\Asset\Packages;
use RZ\Roadiz\Utils\Document\DownscaleImageManager;
use RZ\Roadiz\Utils\Log\LoggerFactory;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Register assets services for dependency injection container.
 */
class AssetsServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Container $container
     * @return Container
     */
    public function register(Container $container)
    {
        $container[DownscaleImageManager::class] = function (Container $c) {
            return new DownscaleImageManager(
                $c['em'],
                $c['assetPackages'],
                $c['logger'],
                $c['config']['assetsProcessing']['driver'],
                $c['config']['assetsProcessing']['maxPixelSize']
            );
        };

        $container[AssetsController::class] = function (Container $c) {
            return new AssetsController(
                $c['kernel'],
                $c['interventionRequest'],
                $c[ManagerRegistry::class],
                $c['twig.environment'],
                $c['settingsBag'],
                $c['assetPackages']
            );
        };

        $container['versionStrategy'] = function () {
            return new EmptyVersionStrategy();
        };

        $container['interventionRequestSupportsWebP'] = function (Container $c) {
            if ($c['config']['assetsProcessing']['driver'] === 'gd' && extension_loaded('gd')) {
                $gd_infos = gd_info();
                return $gd_infos['WebP Support'];
            }
            return false;
        };

        /**
         * Assets packages
         *
         * - default: relative to root
         * - absolute: absolute to root
         * - doc: relative to documents
         * - absolute_doc: absolute to documents
         * @param Container $c
         * @return Packages
         */
        $container['assetPackages'] = function (Container $c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];

            return new Packages(
                $c['versionStrategy'],
                $c['requestStack'],
                $kernel,
                $c['settingsBag']->get('static_domain_name', '') ?? ''
            );
        };

        $container['interventionRequestConfiguration'] = function (Container $c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            $cacheDir = $kernel->getPublicCachePath();
            if (!file_exists($cacheDir)) {
                mkdir($cacheDir);
            }

            $conf = new Configuration();
            $conf->setCachePath($cacheDir);
            $conf->setUsePassThroughCache(true);
            $conf->setImagesPath($kernel->getPublicFilesPath());
            $conf->setDriver($c['config']['assetsProcessing']['driver']);
            $conf->setDefaultQuality($c['config']['assetsProcessing']['defaultQuality']);

            $pngquantPath = $c['config']['assetsProcessing']['pngquantPath'];
            $jpegoptimPath = $c['config']['assetsProcessing']['jpegoptimPath'];

            if (null !== $pngquantPath) {
                $conf->setPngquantPath($pngquantPath);
            }
            if (null !== $jpegoptimPath) {
                $conf->setJpegoptimPath($jpegoptimPath);
            }

            return $conf;
        };

        /**
         * @param Container $c
         * @return array
         */
        $container['interventionRequestSubscribers'] = function (Container $c) {
            $subscribersConfig = $c['config']['assetsProcessing']['subscribers'];
            $subscribers = [];
            foreach ($subscribersConfig as $subscriberConfig) {
                $class = $subscriberConfig['class'];
                $constructArgs = $subscriberConfig['args'];
                $refClass = new \ReflectionClass($class);
                $subscribers[] = $refClass->newInstanceArgs($constructArgs);
            }
            return $subscribers;
        };


        /**
         * @param Container $c
         * @return LoggerInterface
         */
        $container['interventionRequestLogger'] = function (Container $c) {
            /** @var LoggerFactory $factory */
            $factory = $c[LoggerFactory::class];
            return $factory->createLogger('interventionRequest', 'interventionRequest');
        };

        /**
         * @param Container $c
         * @return InterventionRequest
         */
        $container['interventionRequest'] = function (Container $c) {
            $intervention = new InterventionRequest(
                $c['interventionRequestConfiguration'],
                $c['interventionRequestLogger']
            );

            foreach ($c['interventionRequestSubscribers'] as $subscriber) {
                $intervention->addSubscriber($subscriber);
            }

            return $intervention;
        };


        $container->extend('dispatcher', function (EventDispatcher $dispatcher, Container $c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];

            if (!$kernel->isInstallMode()) {
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
                 * Add custom event subscriber to manage image document size and color
                 */
                $dispatcher->addSubscriber(
                    new DocumentSizeSubscriber(
                        $c['assetPackages'],
                        $c['logger']
                    )
                );
                $dispatcher->addSubscriber(
                    new DocumentSvgSizeSubscriber(
                        $c['assetPackages'],
                        $c['logger']
                    )
                );
                $dispatcher->addSubscriber(
                    new DocumentFilesizeSubscriber(
                        $c['assetPackages'],
                        $c['logger']
                    )
                );
                $dispatcher->addSubscriber(
                    new ImageColorDocumentSubscriber(
                        $c['assetPackages'],
                        $c['logger']
                    )
                );
                /*
                 * Add custom event subscriber to create a downscaled version for HD images.
                 */
                $dispatcher->addSubscriber(
                    new RawDocumentsSubscriber(
                        $c[DownscaleImageManager::class]
                    )
                );
                /*
                 * Add custom event subscriber to manage document EXIF
                 */
                $dispatcher->addSubscriber(
                    new ExifDocumentSubscriber(
                        $c[ManagerRegistry::class],
                        $c['assetPackages'],
                        $c['logger']
                    )
                );
            }

            return $dispatcher;
        });


        return $container;
    }
}
