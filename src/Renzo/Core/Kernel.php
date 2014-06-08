<?php 

namespace RZ\Renzo\Core;
/**
* 
*/
class Kernel
{
	private static $instance = null;

	private final function __construct()
	{
		
	}

	public static function getInstance(){

		if (static::$instance === null) {
			static::$instance = new Kernel();
		}

		return static::$instance;
	}
}