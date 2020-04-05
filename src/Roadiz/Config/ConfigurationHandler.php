<?php

declare(strict_types=1);

namespace RZ\Roadiz\Config;

use RZ\Roadiz\Config\Configuration as Config;
use RZ\Roadiz\Core\Exceptions\NoConfigurationFoundException;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Resource\FileResource;

/**
 * Configuration class
 */
abstract class ConfigurationHandler
{
    /**
     * @var array
     */
    protected $configuration;

    /**
     * @var string
     */
    protected $cacheDir;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $cachePath;

    /**
     * @var ConfigCache
     */
    protected $confCache;

    /**
     * @param string $cacheDir
     * @param boolean $debug
     * @param string $path
     */
    public function __construct(string $cacheDir, bool $debug, string $path)
    {
        $this->cacheDir = $cacheDir;
        $this->path = $path;
        $this->cachePath = $this->cacheDir . '/configuration.php';
        $this->confCache = new ConfigCache($this->cachePath, $debug);
    }

    /**
     * @return string
     */
    public function getCacheDir(): string
    {
        return $this->cacheDir;
    }

    /**
     * Load default configuration file.
     *
     * @return array
     */
    public function load(): array
    {
        if (!$this->confCache->isFresh()) {
            $this->setConfiguration($this->loadFromFile($this->path));

            $resources = [
                new FileResource($this->path),
            ];

            $code = '<?php return ' . var_export($this->configuration, true) . ';' . PHP_EOL;
            $this->confCache->write($code, $resources);
        } else {
            $this->configuration = require $this->cachePath;
        }

        return $this->configuration;
    }

    /**
     * @return array
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    /**
     * Set configuration after validating against Roadiz
     * Configuration Schema.
     *
     * @param array $configuration
     * @return $this
     */
    public function setConfiguration(array $configuration)
    {
        $configs = [
            $configuration,
        ];
        $processor = new Processor();
        $roadizConfiguration = new Config();
        $this->configuration = $processor->processConfiguration($roadizConfiguration, $configs);

        return $this;
    }

    /**
     * @param string $file Absolute path to conf file
     * @return string|array|\stdClass
     * @throws NoConfigurationFoundException
     */
    abstract protected function loadFromFile(string $file);

    /**
     * @return bool
     */
    abstract public function writeConfiguration(): bool;
}
