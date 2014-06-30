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

/**
 * Special controller app file for backend themes
 */
class BackendController extends AppController {
	
	protected static $backendTheme = true;

	/**
	 * Check if twig cache must be cleared 
	 */
	protected function handleTwigCache() {

		if (Kernel::getInstance()->isBackendDebug()) {
			try {
				$fs = new Filesystem();
				$fs->remove(array($this->getCacheDirectory()));
			} catch (IOExceptionInterface $e) {
			    echo "An error occurred while deleting backend twig cache directory: ".$e->getPath();
			}
		}
	}
	
}