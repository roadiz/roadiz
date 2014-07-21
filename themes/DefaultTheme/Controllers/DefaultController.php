<?php 

namespace Themes\DefaultTheme\Controllers;

use RZ\Renzo\CMS\Controllers\FrontendController;

/**
* 
*/
class DefaultController extends FrontendController
{
	protected static $specificNodesControllers = array(
		'home',
		// Put here your node which need a specific controller
		// instead of a node-type controller
	);
}