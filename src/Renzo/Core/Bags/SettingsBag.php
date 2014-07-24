<?php 

namespace RZ\Renzo\Core\Bags;

use RZ\Renzo\Core\Kernel;

abstract class SettingsBag
{
	/**
	 * Cached settings values
	 * @var array
	 */
	private static $settings = array();

	/**
	 * @param  string $settingName
	 * @return mixed
	 */
	public static function get( $settingName )
	{
		if (!isset(static::$settings[$settingName]) && 
			Kernel::getInstance()->em() !== null) {

			try {
				static::$settings[$settingName] = 
							Kernel::getInstance()->em()
	            			->getRepository('RZ\Renzo\Core\Entities\Setting')
	            			->getValue($settingName);
			}
			catch (\Exception $e){
				return false;
			}
		}

		return isset(static::$settings[$settingName]) ? static::$settings[$settingName] : false;
	}
}