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
 * @file Configuration.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Console\Tools;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;

/**
 * Configuration class
 */
class Configuration
{
    protected $configuration;
    protected $cacheDir;
    protected $path;

    /**
     * @param string $cacheDir
     * @param string $path
     */
    public function __construct($cacheDir, $path = null)
    {
        $this->cacheDir = $cacheDir;

        if ($path === null) {
            $path = ROADIZ_ROOT . '/conf/config.json';
        }
        $this->path = $path;
    }

    public function getCacheDir()
    {
        return $this->cacheDir;
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
     * @return array
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param array $configuration
     *
     * @return arry $configuration
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;

        return $this;
    }

    /**
     * @return array
     */
    public function getDefaultConfiguration()
    {
        return [
            "appNamespace" => "chooseAnUniqueNameForYourApp",
            "install" => true,
            "devMode" => true,
            "doctrine" => [
                "driver" => "pdo_mysql",
                "host" => "localhost",
                "user" => "",
                "password" => "",
                "dbname" => "",
            ],
            "security" => [
                "secret" => "change#this#secret#very#important",
            ],
            'cacheDriver' => [
                'type' => null,
                'host' => null,
                'port' => null,
            ],
            "mailer" => [
                "type" => "",
                "host" => "localhost",
                "port" => 25,
                "encryption" => false,
                "username" => "",
                "password" => "",
            ],
            "entities" => [
                "src/Roadiz/Core/Entities",
                "src/Roadiz/Core/AbstractEntities",
                "gen-src/GeneratedNodeSources",
            ],
            'rememberMeLifetime' => 2592000,
            'additionalServiceProviders' => [],
            'additionalCommands' => [],
            'assetsProcessing' => [
                'driver' => 'gd',
                'defaultQuality' => 90,
                'maxPixelSize' => 1920,
            ],
        ];
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
     * Set devMode value.
     *
     * @param boolean $value
     *
     * @return $this
     */
    public function setDevMode($value = true)
    {
        if (null !== $this->configuration) {
            $this->configuration["devMode"] = (boolean) $value;
        }

        return $this;
    }

    /**
     * Set install value.
     *
     * @param boolean $value
     *
     * @return $this
     */
    public function setInstall($value = true)
    {
        if (null !== $this->configuration) {
            $this->configuration["install"] = (boolean) $value;
        }

        return $this;
    }

    /**
     * @param string $file Absolute path to conf file
     *
     * @return boolean
     */
    public function loadFromFile($file)
    {
        if (file_exists($file)) {
            $conf = json_decode(file_get_contents($file), true);

            if ($conf !== null && $conf !== false) {
                $this->setConfiguration($conf);

                return true;
            }
        }

        return false;
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
