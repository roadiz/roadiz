<?php
/*
 * Copyright REZO ZERO 2014
 *
 * Description
 *
 * @file Configuration.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */

namespace Themes\Install\Controllers;

/**
* Configuration class
*/
class Configuration
{
    protected $configuration;

    /**
     * Configuration constructor
     */
    public function __construct()
    {
        // Try to load existant configuration
        if (false === $this->loadFromFile(RENZO_ROOT . '/conf/config.json')) {
            if (false === $this->loadFromFile(RENZO_ROOT . '/conf/config.default.json')) {
                $this->setConfiguration($this->getDefaultConfiguration());
            }
        }
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
        return array(
            "appNamespace" => "chooseAnUniqueNameForYourApp",
            "install" => true,
            "devMode" => true,
            "doctrine" => array(
                "driver" =>   "pdo_mysql",
                "host" =>     "localhost",
                "user" =>     "",
                "password" => "",
                "dbname" =>   ""
            ),
            "security" => array(
                "secret" => "change#this#secret#very#important"
            )
        );
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
        $writePath = RENZO_ROOT . '/conf/config.json';

        if (file_exists($writePath)) {
            unlink($writePath);
        }

        file_put_contents($writePath, json_encode($this->getConfiguration(), JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE));
    }
}
