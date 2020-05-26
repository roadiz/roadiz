<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Console\Helper;

use Symfony\Component\Console\Helper\Helper;

/**
 * ConfigurationHelper.
 */
class ConfigurationHelper extends Helper
{
    private $configuration;

    /**
     * @param array $configuration
     */
    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @return array
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'configuration';
    }
}
