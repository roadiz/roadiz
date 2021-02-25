<?php
declare(strict_types=1);

namespace RZ\Roadiz\Config;

use RZ\Roadiz\Core\Exceptions\NoYamlConfigurationFoundException;

/**
 * @package RZ\Roadiz\Console\Tools
 * @deprecated Use ConfigurationHandler with YamlConfigurationLoader
 */
final class YamlConfigurationHandler extends ConfigurationHandler
{
    /**
     * @param string $file File path
     * @return string|array|\stdClass
     * @throws NoYamlConfigurationFoundException
     */
    protected function loadFromFile(string $file)
    {
        return $this->configurationLoader->loadFromFile($file);
    }
}
