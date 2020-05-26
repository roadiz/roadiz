<?php
declare(strict_types=1);

namespace RZ\Roadiz\Config;

use RZ\Roadiz\Core\Exceptions\NoYamlConfigurationFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

/**
 * Class YamlConfiguration
 * @package RZ\Roadiz\Console\Tools
 */
class YamlConfigurationHandler extends ConfigurationHandler
{
    /**
     * @param string $file File path
     * @return string|array|\stdClass
     * @throws NoYamlConfigurationFoundException
     */
    protected function loadFromFile(string $file)
    {
        $fileSystem = new Filesystem();
        if ($fileSystem->exists($file)) {
            $file = new File($file);
            if (!$file->isReadable()) {
                throw new FileException($file->getPathname() . ' file is not readable.');
            }
            $parser = new Parser();
            return $parser->parse(file_get_contents($file->getPathname()));
        }

        throw new NoYamlConfigurationFoundException();
    }

    /**
     * @return bool
     */
    public function writeConfiguration(): bool
    {
        $file = new File($this->path);
        if (!$file->isWritable()) {
            throw new FileException($file->getPathname() . ' file is not writable.');
        }
        try {
            $dumper = new Dumper();
            $yaml = $dumper->dump($this->getConfiguration(), 4);

            file_put_contents($file->getPathname(), $yaml);
            return true;
        } catch (ParseException $e) {
            return false;
        }
    }
}
