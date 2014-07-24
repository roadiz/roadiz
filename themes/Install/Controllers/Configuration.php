<?php 

namespace Themes\Install\Controllers;


/**
* 
*/
class Configuration
{
	protected $configuration;

	function __construct()
	{	
		// Try to load existant configuration
		if ($this->loadFromFile( RENZO_ROOT . '/conf/config.json' ) === false) {
			if ($this->loadFromFile( RENZO_ROOT . '/conf/config.default.json' ) === false) {
				$this->setConfiguration($this->getDefaultConfiguration());
			}
		}
	}

	/**
	 * @return array
	 */
	public function getConfiguration() {
	    return $this->configuration;
	}
	/**
	 * @param array
	 */
	public function setConfiguration($configuration) {
	    $this->configuration = $configuration;
	    return $this;
	}

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
	 * [loadFromFile description]
	 * @param  string $file Absolute path to conf file
	 * @return boolean
	 */
	public function loadFromFile( $file )
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

	public function writeConfiguration()
	{
		$writePath = RENZO_ROOT . '/conf/config.json';

		if (file_exists($writePath)) {
			unlink($writePath);
		}

		file_put_contents($writePath, json_encode($this->getConfiguration(), JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE));
	}
}