<?php
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
 * @file ConfigurationHandler.php
 * @author ambroisemaupate
 *
 */
namespace RZ\Roadiz\Config;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Config\Configuration as Config;
use Doctrine\ORM\Tools\Setup;
use RZ\Roadiz\Core\Exceptions\NoConfigurationFoundException;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Resource\FileResource;

/**
 * Configuration class
 */
class ConfigurationHandler
{
    /**
     * @var array
     */
    protected $configuration;

    /**
     * @var string
     */
    protected $cacheDir;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $cachePath;

    /**
     * @var ConfigCache
     */
    protected $confCache;

    /**
     * @param string $cacheDir
     * @param boolean $debug
     * @param string $path
     */
    public function __construct($cacheDir, $debug, $path)
    {
        $this->cacheDir = $cacheDir;
        $this->path = $path;
        $this->cachePath = $this->cacheDir . '/configuration.php';
        $this->confCache = new ConfigCache($this->cachePath, $debug);
    }

    /**
     * @return string
     */
    public function getCacheDir()
    {
        return $this->cacheDir;
    }

    /**
     * Load default configuration file.
     *
     * @return array
     */
    public function load()
    {
        if (!$this->confCache->isFresh()) {
            $this->setConfiguration($this->loadFromFile($this->path));

            $resources = [
                new FileResource($this->path),
            ];

            $code = '<?php return ' . var_export($this->configuration, true) . ';' . PHP_EOL;
            $this->confCache->write($code, $resources);
        } else {
            $this->configuration = require $this->cachePath;
        }

        return $this->configuration;
    }

    /**
     * @return array
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * Set configuration after validating against Roadiz
     * Configuration Schema.
     *
     * @param array $configuration
     * @return $this
     */
    public function setConfiguration(array $configuration)
    {
        $configs = [
            $configuration,
        ];
        $processor = new Processor();
        $roadizConfiguration = new Config();
        $this->configuration = $processor->processConfiguration($roadizConfiguration, $configs);

        return $this;
    }

    /**
     * Test database connexion with given configuration.
     *
     * @param array $connexion Doctrine array parameters
     *
     * @throws \PDOException
     */
    public function testDoctrineConnexion($connexion = [])
    {
        $config = Setup::createAnnotationMetadataConfiguration(
            [],
            true,
            null,
            null,
            false
        );

        $em = EntityManager::create($connexion, $config);
        $em->getConnection()->connect();
    }

    /**
     * @param string $file Absolute path to conf file
     * @return array
     * @throws NoConfigurationFoundException
     */
    protected function loadFromFile($file)
    {
        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true);
        }

        throw new NoConfigurationFoundException();
    }

    /**
     * @return void
     */
    public function writeConfiguration()
    {
        if (file_exists($this->path)) {
            unlink($this->path);
        }

        file_put_contents(
            $this->path,
            json_encode(
                $this->getConfiguration(),
                JSON_PRETTY_PRINT|JSON_NUMERIC_CHECK|JSON_UNESCAPED_UNICODE
            )
        );
    }
}
