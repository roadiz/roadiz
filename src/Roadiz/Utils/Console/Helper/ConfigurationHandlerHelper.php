<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Console\Helper;

use RZ\Roadiz\Config\ConfigurationHandlerInterface;
use Symfony\Component\Console\Helper\Helper;

class ConfigurationHandlerHelper extends Helper
{
    protected ConfigurationHandlerInterface $configurationHandler;

    public function __construct(ConfigurationHandlerInterface $configurationHandler)
    {
        $this->configurationHandler = $configurationHandler;
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'configurationHandler';
    }

    /**
     * @return ConfigurationHandlerInterface
     */
    public function getConfigurationHandler(): ConfigurationHandlerInterface
    {
        return $this->configurationHandler;
    }
}
