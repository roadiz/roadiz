<?php
declare(strict_types=1);

namespace RZ\Roadiz\Config;

use RZ\Roadiz\Config\Configuration as Config;
use RZ\Roadiz\Config\Loader\ConfigurationLoader;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Resource\FileResource;

class ConfigurationHandler implements ConfigurationHandlerInterface
{
    /**
     * @var array
     */
    protected $configuration;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var ConfigCache|null
     */
    protected $confCache;

    /**
     * @var Config
     */
    protected $configurationTree;

    /**
     * @var ConfigurationLoader
     */
    protected $configurationLoader;

    /**
     * @param Config $configurationTree
     * @param string $path
     * @param ConfigurationLoader $configurationLoader
     * @param ConfigCache|null $confCache
     */
    public function __construct(
        Config $configurationTree,
        string $path,
        ConfigurationLoader $configurationLoader,
        ?ConfigCache $confCache = null
    ) {
        $this->path = $path;
        $this->confCache = $confCache;
        $this->configurationTree = $configurationTree;
        $this->configurationLoader = $configurationLoader;
    }

    /**
     * Load default configuration file.
     *
     * @return array
     */
    public function load(): array
    {
        if (null !== $this->confCache) {
            if (!$this->confCache->isFresh()) {
                $rawConfiguration = $this->configurationLoader->loadFromFile($this->path);
                $this->setConfiguration($rawConfiguration);

                $resources = [
                    new FileResource($this->path),
                ];

                $this->confCache->write($this->generateConfigurationCacheSource($rawConfiguration), $resources);
            } else {
                $this->setConfiguration(require $this->confCache->getPath());
            }
        } else {
            $this->setConfiguration($this->configurationLoader->loadFromFile($this->path));
        }

        return $this->configuration;
    }

    /**
     * @param string|array|\stdClass $rawConfiguration
     * @return string
     */
    protected function generateConfigurationCacheSource($rawConfiguration): string
    {
        return '<?php return ' . var_export($rawConfiguration, true) . ';' . PHP_EOL;
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
        $this->configuration = $processor->processConfiguration($this->configurationTree, $configs);

        return $this;
    }

    /**
     * @deprecated Use your configuration loader independently from handler
     */
    public function writeConfiguration(): void
    {
        $this->configurationLoader->saveToFile($this->path, $this->configuration);
    }
}
