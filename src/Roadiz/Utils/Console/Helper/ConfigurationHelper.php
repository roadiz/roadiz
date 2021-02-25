<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Console\Helper;

use Symfony\Component\Console\Helper\Helper;

class ConfigurationHelper extends Helper
{
    private array $configuration;

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
    public function getConfiguration(): array
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
