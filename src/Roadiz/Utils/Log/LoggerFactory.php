<?php
/**
 * Copyright (c) 2020. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file LoggerFactory.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

declare(strict_types=1);

namespace RZ\Roadiz\Utils\Log;

use Gelf\Publisher;
use Gelf\Transport\HttpTransport;
use Monolog\Handler\RavenHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use RZ\Roadiz\Core\KernelInterface;
use RZ\Roadiz\Utils\Log\Handler\TolerantGelfHandler;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class LoggerFactory
{
    /**
     * @var KernelInterface
     */
    protected $kernel;
    /**
     * @var array
     */
    protected $loggerConfig;

    /**
     * LoggerFactory constructor.
     *
     * @param KernelInterface $kernel
     * @param array           $loggerConfig
     */
    public function __construct(KernelInterface $kernel, array $loggerConfig = [])
    {
        $this->kernel = $kernel;
        $this->loggerConfig = $loggerConfig;
    }

    protected function getLoggerPath(string $name = null)
    {
        return $this->kernel->getLogDir() . '/' .
            ($name ?: $this->kernel->getName()). '_' .
            $this->kernel->getEnvironment().'.log';
    }

    protected function getHandlers(string $name = null): array
    {
        $handlers = [];

        if (!empty($this->loggerConfig) &&
            !empty($this->loggerConfig['handlers'])) {
            foreach ($this->loggerConfig['handlers'] as $config) {
                if (empty($config['level'])) {
                    throw new InvalidConfigurationException('A monolog handler must define a log "level".');
                }
                if (!empty($config['type'])) {
                    switch ($config['type']) {
                        case 'default':
                            $handlers[] = new StreamHandler(
                                $this->getLoggerPath($name),
                                constant('\Monolog\Logger::'.$config['level'])
                            );
                            break;
                        case 'stream':
                            if (empty($config['path'])) {
                                throw new InvalidConfigurationException(
                                    'A monolog StreamHandler must define a log "path".'
                                );
                            }
                            $handlers[] = new StreamHandler(
                                $config['path'],
                                constant('\Monolog\Logger::'.$config['level'])
                            );
                            break;
                        case 'syslog':
                            if (empty($config['ident'])) {
                                throw new InvalidConfigurationException(
                                    'A monolog SyslogHandler must define a log "ident".'
                                );
                            }
                            $handlers[] = new SyslogHandler(
                                $config['ident'],
                                LOG_USER,
                                constant('\Monolog\Logger::'.$config['level'])
                            );
                            break;
                        case 'gelf':
                            if (empty($config['url'])) {
                                throw new InvalidConfigurationException(
                                    'A monolog GELFHandler must define a log "url".'
                                );
                            }
                            $publisher = new Publisher(HttpTransport::fromUrl($config['url']));

                            $handlers[] = new TolerantGelfHandler(
                                $publisher,
                                constant('\Monolog\Logger::'.$config['level'])
                            );
                            break;
                        case 'sentry':
                            if (empty($config['url'])) {
                                throw new InvalidConfigurationException(
                                    'A Sentry handler must declare a DSN "url".'
                                );
                            }
                            if (function_exists('\Sentry\init') &&
                                class_exists('\Sentry\Monolog\Handler')) {
                                $sentryConfig = ['dsn' => $config['url']];
                                \Sentry\init($sentryConfig);
                                $client = \Sentry\ClientBuilder::create($sentryConfig)->getClient();
                                $handler = new \Sentry\Monolog\Handler(
                                    new \Sentry\State\Hub($client),
                                    constant('\Monolog\Logger::'.$config['level'])
                                );
                                $handlers[] = $handler;
                            } elseif (class_exists('Raven_Client')) {
                                $client = new \Raven_Client($config['url']);
                                $error_handler = new \Raven_ErrorHandler($client);
                                $error_handler->registerExceptionHandler();
                                $error_handler->registerErrorHandler();
                                $error_handler->registerShutdownFunction();
                                $handler = new RavenHandler(
                                    $client,
                                    constant('\Monolog\Logger::'.$config['level'])
                                );
                                $handlers[] = $handler;
                            } else {
                                throw new InvalidConfigurationException(
                                    'Sentry handler requires sentry/sentry library.'
                                );
                            }
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
            if (true === $this->kernel->isDebug()) {
                $handlers[] = new StreamHandler($this->getLoggerPath($name), Logger::DEBUG);
            } else {
                $handlers[] = new StreamHandler($this->getLoggerPath($name), Logger::NOTICE);
            }
        }

        return $handlers;
    }

    /**
     * @param string $name
     *
     * @return LoggerInterface
     */
    public function createLogger(string $name = 'roadiz', string $filename = 'roadiz'): LoggerInterface
    {
        $logger = new Logger($name);

        foreach ($this->getHandlers($filename) as $handler) {
            $logger->pushHandler($handler);
        }

        return $logger;
    }
}
