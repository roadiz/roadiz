<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Log;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use RZ\Roadiz\Core\KernelInterface;
use RZ\Roadiz\Utils\Log\Handler\TolerantGelfHandler;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class LoggerFactory
{
    protected KernelInterface $kernel;
    protected array $loggerConfig;

    /**
     * @param KernelInterface $kernel
     * @param array           $loggerConfig
     */
    public function __construct(KernelInterface $kernel, array $loggerConfig = [])
    {
        $this->kernel = $kernel;
        $this->loggerConfig = $loggerConfig;
    }

    protected function getLoggerPath(string $name = null): string
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
                        case 'rotating_file':
                            if (empty($config['path'])) {
                                throw new InvalidConfigurationException(
                                    'A monolog StreamHandler must define a log "path".'
                                );
                            }
                            if (null !== $name) {
                                $basename = pathinfo($config['path'], PATHINFO_FILENAME);
                                $dirname = pathinfo($config['path'], PATHINFO_DIRNAME);
                                $extension = pathinfo($config['path'], PATHINFO_EXTENSION);
                                if ($basename !== $name) {
                                    $filename = $dirname . '/' .  $name . '.' . $extension;
                                } else {
                                    $filename = $config['path'];
                                }
                            } else {
                                $filename = $config['path'];
                            }
                            $handlers[] = new RotatingFileHandler(
                                $filename,
                                $config['max_files'],
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
                            if (class_exists('\Gelf\Publisher') &&
                                class_exists('\Gelf\Transport\HttpTransport')) {
                                $publisher = new \Gelf\Publisher(\Gelf\Transport\HttpTransport::fromUrl($config['url']));
                                $handlers[] = new TolerantGelfHandler(
                                    $publisher,
                                    constant('\Monolog\Logger::'.$config['level'])
                                );
                            } else {
                                throw new InvalidConfigurationException(
                                    'A monolog GELFHandler requires "graylog2/gelf-php" library.'
                                );
                            }
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
     * @param string $filename
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
