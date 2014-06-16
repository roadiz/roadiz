<?php 
/**
 * Copyright REZO ZERO 2014
 * 
 * 
 * 
 *
 * @file FrontendController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\CMS\Controllers;

use Symfony\Component\HttpFoundation\Response;

/**
 * 
 * Frontend controller to handle node request
 * This class must be inherited in order to create a new theme
 * 
 */
class FrontendController extends AppController {

	protected static $themeName =      'Default theme';
	protected static $themeAuthor =    'Ambroise Maupate';
	protected static $themeCopyright = 'REZO ZERO';
	protected static $themeDir =       'DefaultTheme';
	protected static $backendTheme = 	false;

	/**
	 * Default action for default URL (homepage)
	 * @param  RZ\Renzo\Core\Entities\Node $node Requested node for given URL
	 * @param  RZ\Renzo\Core\Entities\Translation $translation
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function indexAction( $node, $translation )
	{
		$this->assignation['node'] = $node;
		$this->assignation['translation'] = $translation;

		return new Response(
		    $this->getTwig()->render('home.html.twig', $this->assignation),
		    Response::HTTP_OK,
		    array('content-type' => 'text/html')
		);
	}
}