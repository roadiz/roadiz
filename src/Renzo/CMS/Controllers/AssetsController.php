<?php 
/**
 * Copyright REZO ZERO 2014
 * 
 * 
 * 
 *
 * @file BackendController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\CMS\Controllers;

use RZ\Renzo\Core\Kernel;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;


use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

/**
 * Special controller app file for assets managment with SLIR
 */
class AssetsController extends AppController {
	
	/**
	 * Override default constructor
	 */
	public function __construct(){ }

	/**
	 * 
	 * @return RouteCollection
	 */
	public static function getRoutes()
	{
		$locator = new FileLocator(array(
			RENZO_ROOT.'/src/Renzo/CMS/Resources'
		));

		if (file_exists(RENZO_ROOT.'/src/Renzo/CMS/Resources/assetsRoutes.yml')) {
			$loader = new YamlFileLoader($locator);
			return $loader->load('assetsRoutes.yml');
		}

		return null;
	}

	/**
	 * Handle images resize with SLIR vendor
	 * 
	 * @param  string $queryString
	 * @param  string $filename
	 * @return void
	 */
	public function slirAction($queryString, $filename)
	{
		define('SLIR_CONFIG_CLASSNAME','\RZ\Renzo\Core\Utils\SLIRConfig');
		
		$slir = new \SLIR\SLIR();
		$slir->processRequestFromURL();

		// SLIR handle response by itself
	}
}