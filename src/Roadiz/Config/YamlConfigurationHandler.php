<?php
declare(strict_types=1);
/**
 * Copyright (c) 2016.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file YamlConfigurationHandler.php
 * @author ambroisemaupate
 *
 */
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
