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
 * @file AssetsServiceProvider.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Services;

use AM\InterventionRequest\Configuration;
use AM\InterventionRequest\InterventionRequest;
use AM\InterventionRequest\ShortUrlExpander;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\Asset\Packages;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;

/**
 * Register assets services for dependency injection container.
 */
class AssetsServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Container $container [description]
     * @return Container
     */
    public function register(Container $container)
    {
        $container['versionStrategy'] = function () {
            return new EmptyVersionStrategy();
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
                $c['settingsBag']->get('static_domain_name'),
                $kernel->isPreview()
            );
        };

        $container['interventionRequestConfiguration'] = function ($c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            $cacheDir = $kernel->getPublicCachePath();
            if (!file_exists($cacheDir)) {
                mkdir($cacheDir);
            }

            $imageDriver = !empty($c['config']['assetsProcessing']['driver']) ? $c['config']['assetsProcessing']['driver'] : 'gd';
            $defaultQuality = !empty($c['config']['assetsProcessing']['defaultQuality']) ? (int) $c['config']['assetsProcessing']['defaultQuality'] : 90;
            $pngquantPath = !empty($c['config']['assetsProcessing']['pngquantPath']) ? $c['config']['assetsProcessing']['pngquantPath'] : null;
            $jpegoptimPath = !empty($c['config']['assetsProcessing']['jpegoptimPath']) ? $c['config']['assetsProcessing']['jpegoptimPath'] : null;

            $conf = new Configuration();
            $conf->setCachePath($cacheDir);
            $conf->setUsePassThroughCache(true);
            $conf->setImagesPath($kernel->getPublicFilesPath());
            $conf->setDriver($imageDriver);
            $conf->setDefaultQuality($defaultQuality);

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
         * @return Logger
         */
        $container['interventionRequestLogger'] = function (Container $c) {
            $log = new Logger('InterventionRequest');
            $log->pushHandler(new StreamHandler($c['kernel']->getLogDir() . '/interventionRequest.log', Logger::INFO));
            return $log;
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

        return $container;
    }
}
