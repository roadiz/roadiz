<?php
/*
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
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
 * @file YamlConfiguration.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Console\Tools;

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * YamlConfiguration class
 */
class YamlConfiguration extends Configuration
{
    protected $cachePath;
    protected $confCache;

    /**
     *
     * @param string  $cacheDir
     * @param boolean $debug
     * @param string  $path
     */
    public function __construct($cacheDir, $debug, $path)
    {
        parent::__construct($cacheDir, $path);

        $this->cachePath = $this->cacheDir . '/configuration.php';
        $this->confCache = new ConfigCache($this->cachePath, $debug);
    }

    /**
     * Load default configuration file
     *
     * @return boolean
     */
    public function load()
    {
        // Try to load existant configuration
        return $this->loadFromFile($this->path);
    }

    /**
     * @param string $file Absolute path to conf file
     *
     * @return boolean
     */
    public function loadFromFile($file)
    {
        if (!$this->confCache->isFresh()) {
            $configuration = Yaml::parse($this->path);
            $resources = [
                new FileResource($this->path),
            ];

            // le code pour le « UserMatcher » est généré quelque part d'autre
            $code = '<?php return ' . var_export($configuration, true) . ';' . PHP_EOL;

            $this->confCache->write($code, $resources);
        } else {
            $configuration = include $this->cachePath;
        }

        $this->setConfiguration($configuration);

        return true;
    }

    /**
     * @return bool
     */
    public function writeConfiguration()
    {
        if (file_exists($this->path)) {
            unlink($this->path);
        }

        try {
            $dumper = new Dumper();
            $yaml = $dumper->dump($this->getConfiguration(), 4);

            file_put_contents($this->path, $yaml);
            return true;
        } catch (ParseException $e) {
            return false;
        }
    }
}
