<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Console\Helper;

use RZ\Roadiz\Config\ConfigurationHandler;
use Symfony\Component\Console\Helper\Helper;

class ConfigurationHandlerHelper extends Helper
{
    /**
     * @var ConfigurationHandler
     */
    protected $configurationHandler;

    public function __construct(ConfigurationHandler $configurationHandler)
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
     * @return ConfigurationHandler
     */
    public function getConfigurationHandler()
    {
        return $this->configurationHandler;
    }
}
