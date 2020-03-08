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
 * @file LoggerServiceProvider.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Services;

use Gelf\Publisher;
use Gelf\Transport\HttpTransport;
use Monolog\Handler\RavenHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Logger;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\Log\Handler\DoctrineHandler;
use RZ\Roadiz\Utils\Log\Handler\TolerantGelfHandler;
use RZ\Roadiz\Utils\Log\LoggerFactory;
use RZ\Roadiz\Utils\Log\Processor\RequestProcessor;
use RZ\Roadiz\Utils\Log\Processor\TokenStorageProcessor;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class LoggerServiceProvider.
 *
 * @package RZ\Roadiz\Core\Services
 */
class LoggerServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Container $container
     * @return Container
     */
    public function register(Container $container)
    {
        $container[LoggerFactory::class] = function (Container $c) {
            return new LoggerFactory(
                $c['kernel'],
                !empty($c['config']['monolog']) ? $c['config']['monolog'] : []
            );
        };

        $container['logger.doctrine'] = function (Container $c) {
            /** @var LoggerFactory $factory */
            $factory = $c[LoggerFactory::class];
            $log = $factory->createLogger('doctrine', 'doctrine');

            /*
             * Add processors
             */
            /** @var RequestStack $requestStack */
            $requestStack = $c['requestStack'];
            $log->pushProcessor(new RequestProcessor($requestStack));
            $log->pushProcessor(new TokenStorageProcessor($c['securityTokenStorage']));

            return $log;
        };

        $container['logger.cli'] = function (Container $c) {
            /** @var LoggerFactory $factory */
            $factory = $c[LoggerFactory::class];
            return $factory->createLogger('roadiz', 'cli');
        };

        $container['logger'] = function (Container $c) {
            /** @var LoggerFactory $factory */
            $factory = $c[LoggerFactory::class];
            $log = $factory->createLogger('roadiz', 'roadiz');

            /*
             * Only activate doctrine logger for production.
             */
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            if (null !== $c['em'] &&
                false === $kernel->isInstallMode() &&
                $kernel->getEnvironment() == 'prod') {
                $log->pushHandler(new DoctrineHandler(
                    $c['em'],
                    $c['securityTokenStorage'],
                    $c['requestStack'],
                    Logger::INFO
                ));
            }

            /*
             * Add processors
             */
            /** @var RequestStack $requestStack */
            $requestStack = $c['requestStack'];
            $log->pushProcessor(new RequestProcessor($requestStack));
            $log->pushProcessor(new TokenStorageProcessor($c['securityTokenStorage']));

            return $log;
        };

        $container['logger.security'] = function (Container $c) {
            /** @var LoggerFactory $factory */
            $factory = $c[LoggerFactory::class];
            $log = $factory->createLogger('security', 'security');

            /*
             * Only activate doctrine logger for production.
             */
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            if (null !== $c['em'] &&
                false === $kernel->isInstallMode() &&
                $kernel->getEnvironment() == 'prod') {
                $log->pushHandler(new DoctrineHandler(
                    $c['em'],
                    $c['securityTokenStorage'],
                    $c['requestStack'],
                    Logger::INFO
                ));
            }

            /*
             * Add processors
             */
            /** @var RequestStack $requestStack */
            $requestStack = $c['requestStack'];
            $log->pushProcessor(new RequestProcessor($requestStack));
            $log->pushProcessor(new TokenStorageProcessor($c['securityTokenStorage']));

            return $log;
        };

        return $container;
    }
}
