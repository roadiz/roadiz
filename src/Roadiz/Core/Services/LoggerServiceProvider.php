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

use Monolog\Handler\GelfHandler;
use Monolog\Handler\NewRelicHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Logger;
use Monolog\Processor\WebProcessor;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Log\DoctrineHandler;
use RZ\Roadiz\Utils\LogProcessors\RequestProcessor;
use RZ\Roadiz\Utils\LogProcessors\TokenStorageProcessor;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

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
        $container['logger.handlers'] = function ($c) {
            $handlers = [];
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];

            if (!empty($c['config']['monolog']) &&
                !empty($c['config']['monolog']['handlers'])) {
                foreach ($c['config']['monolog']['handlers'] as $config) {
                    if (empty($config['level'])) {
                        throw new InvalidConfigurationException('A monolog handler must define a log "level".');
                    }
                    if (!empty($config['type'])) {
                        switch ($config['type']) {
                            case 'default':
                                $handlers[] = new StreamHandler($c['logger.path'], Logger::NOTICE);
                                if (null !== $c['em'] &&
                                    true === $kernel->isDebug()) {
                                    $handlers[] = new StreamHandler($c['logger.path'], Logger::DEBUG);
                                }
                                break;
                            case 'stream':
                                if (empty($config['path'])) {
                                    throw new InvalidConfigurationException('A monolog StreamHandler must define a log "path".');
                                }
                                $handlers[] = new StreamHandler(
                                    $config['path'],
                                    constant('\Monolog\Logger::'.$config['level'])
                                );
                                break;
                            case 'syslog':
                                if (empty($config['ident'])) {
                                    throw new InvalidConfigurationException('A monolog SyslogHandler must define a log "ident".');
                                }
                                $handlers[] = new SyslogHandler(
                                    $config['ident'],
                                    LOG_USER,
                                    constant('\Monolog\Logger::'.$config['level'])
                                );
                                break;
                            case 'newrelic':
                                if (empty($config['app_name'])) {
                                    throw new InvalidConfigurationException('A monolog NewRelicHandler must define a log "app_name".');
                                }
                                $handlers[] = new NewRelicHandler(
                                    constant('\Monolog\Logger::'.$config['level']),
                                    true,
                                    $config['app_name']
                                );
                                break;
                            case 'gelf':
                                if (empty($config['publisher'])) {
                                    throw new InvalidConfigurationException('A monolog NewRelicHandler must define a log "publisher".');
                                }
                                if (empty($config['publisher']['hostname']) &&
                                    empty($config['publisher']['id'])) {
                                    throw new InvalidConfigurationException('A monolog NewRelicHandler must define a for the publisher either the hostname or the id.');
                                }
                                $handlers[] = new GelfHandler(
                                    $config['publisher'],
                                    constant('\Monolog\Logger::'.$config['level'])
                                );
                                break;
                        }
                    } else {
                        throw new InvalidConfigurationException('A monolog handler must have a "type".');
                    }
                }
            } else {
                /*
                 * Default handlers
                 */
                $handlers[] = new StreamHandler($c['logger.path'], Logger::NOTICE);
                if (null !== $c['em'] &&
                    true === $kernel->isDebug()) {
                    $handlers[] = new StreamHandler($c['logger.path'], Logger::DEBUG);
                }
            }

            /*
             * Only activate doctrine logger for production.
             */
            if (null !== $c['em'] &&
                false === $kernel->isInstallMode() &&
                $kernel->getEnvironment() == 'prod') {
                $handlers[] = new DoctrineHandler(
                    $c['em'],
                    $c['securityTokenStorage'],
                    $c['request'],
                    Logger::INFO
                );
            }

            return $handlers;
        };

        $container['logger.path'] = function ($c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            return $kernel->getLogDir() . '/' .
            $kernel->getName(). '_' .
            $kernel->getEnvironment().'.log';
        };

        $container['logger'] = function ($c) {
            $log = new Logger('roadiz');

            foreach ($c['logger.handlers'] as $handler) {
                $log->pushHandler($handler);
            }

            /*
             * Add processors
             */
            $log->pushProcessor(new WebProcessor());
            $log->pushProcessor(new TokenStorageProcessor($c['securityTokenStorage']));

            return $log;
        };

        return $container;
    }
}
