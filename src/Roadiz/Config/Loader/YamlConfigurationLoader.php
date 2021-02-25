<?php
declare(strict_types=1);

namespace RZ\Roadiz\Config\Loader;

use RZ\Roadiz\Core\Exceptions\NoYamlConfigurationFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;

/**
 * @package RZ\Roadiz\Config\Loader
 */
final class YamlConfigurationLoader implements ConfigurationLoader
{
    /**
     * @inheritDoc
     */
    public function loadFromFile(string $path)
    {
        $fileSystem = new Filesystem();
        if ($fileSystem->exists($path)) {
            $file = new File($path);
            if (!$file->isReadable()) {
                throw new FileException($file->getPathname() . ' file is not readable.');
            }
            $parser = new Parser();
            return $parser->parse(file_get_contents($file->getPathname()));
        }

        throw new NoYamlConfigurationFoundException();
    }

    /**
     * @inheritDoc
     */
    public function saveToFile(string $path, $configuration): void
    {
        $file = new File($path);
        if (!$file->isWritable()) {
            throw new FileException($file->getPathname() . ' file is not writable.');
        }
        $dumper = new Dumper();
        $yaml = $dumper->dump($configuration, 4);

        file_put_contents($file->getPathname(), $yaml);
    }
}
