<?php
declare(strict_types=1);

namespace RZ\Roadiz\Config;

interface ConfigurationHandlerInterface
{
    public function load(): array;

    public function setConfiguration(array $configuration);

    public function getConfiguration(): array;

    /**
     * @deprecated Use your configuration loader independently from handler
     */
    public function writeConfiguration(): void;
}
