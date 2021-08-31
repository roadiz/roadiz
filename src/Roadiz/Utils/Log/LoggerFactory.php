<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Log;

use Monolog\Handler\HandlerInterface;
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

    protected function createStreamHandler(?string $name, array &$config): ?HandlerInterface
    {
        $path = $this->getLoggerPath($name);
        if (!empty($config['path'])) {
            $path = $config['path'];
        }
        return new StreamHandler(
            $path,
            constant('\Monolog\Logger::'.$config['level'])
        );
    }

    protected function createRotatingFileHandler(?string $name, array &$config): ?HandlerInterface
    {
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
        return new RotatingFileHandler(
            $filename,
            $config['max_files'],
            constant('\Monolog\Logger::'.$config['level'])
        );
    }

    protected function createSyslogHandler(?string $name, array &$config): ?HandlerInterface
    {
        if (empty($config['ident'])) {
            throw new InvalidConfigurationException(
                'A monolog SyslogHandler must define a log "ident".'
            );
        }
        return new SyslogHandler(
            $config['ident'],
            LOG_USER,
            constant('\Monolog\Logger::'.$config['level'])
        );
    }

    protected function createGelfHandler(?string $name, array &$config): ?HandlerInterface
    {
        if (empty($config['url'])) {
            // Allow empty URL to disable handler from DotEnv without
            // removing configuration lines.
            return null;
        }
        if (class_exists('\Gelf\Publisher') &&
            class_exists('\Gelf\PublisherInterface') &&
            class_exists('\Gelf\Transport\HttpTransport')) {
            $publisher = new \Gelf\Publisher(\Gelf\Transport\HttpTransport::fromUrl($config['url']));
            return new TolerantGelfHandler(
                $publisher,
                constant('\Monolog\Logger::'.$config['level'])
            );
        } else {
            throw new InvalidConfigurationException(
                'A monolog GELFHandler requires "graylog2/gelf-php" library.'
            );
        }
    }

    protected function createSentryHandler(?string $name, array &$config): ?HandlerInterface
    {
        if (empty($config['url'])) {
            // Allow empty URL to disable handler from DotEnv without
            // removing configuration lines.
            return null;
        }
        if (function_exists('\Sentry\init') &&
            class_exists('\Sentry\Monolog\Handler')) {
            $sentryConfig = ['dsn' => $config['url']];
            \Sentry\init($sentryConfig);
            $client = \Sentry\ClientBuilder::create($sentryConfig)->getClient();
            return new \Sentry\Monolog\Handler(
                new \Sentry\State\Hub($client),
                constant('\Monolog\Logger::'.$config['level'])
            );
        } else {
            throw new InvalidConfigurationException(
                'Sentry handler requires sentry/sentry library.'
            );
        }
    }

    /**
     * @param string|null $name
     * @return array<HandlerInterface>
     */
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
                        case 'stream':
                            $handlers[] = $this->createStreamHandler($name, $config);
                            break;
                        case 'rotating_file':
                            $handlers[] = $this->createRotatingFileHandler($name, $config);
                            break;
                        case 'syslog':
                            $handlers[] = $this->createSyslogHandler($name, $config);
                            break;
                        case 'gelf':
                            $handlers[] = $this->createGelfHandler($name, $config);
                            break;
                        case 'sentry':
                            $handlers[] = $this->createSentryHandler($name, $config);
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

        return array_filter($handlers);
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
