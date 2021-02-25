<?php
declare(strict_types=1);

namespace RZ\Roadiz\Config\Loader;

interface ConfigurationLoader
{
    /**
     * @param string $path
     * @return string|array|\stdClass
     */
    public function loadFromFile(string $path);

    /**
     * @param string $path
     * @param string|array|\stdClass $configuration
     */
    public function saveToFile(string $path, $configuration): void;
}
