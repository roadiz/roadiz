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

use RZ\Renzo\Core\Kernel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Entities\Node;

/**
 * 
 * Frontend controller to handle node request
 * This class must be inherited in order to create a new theme
 * 
 */
class FrontendController {

	protected $themeName =      'Default theme';
	protected $themeAuthor =    'Ambroise Maupate';
	protected $themeCopyright = 'REZO ZERO';
	protected $themeDir =       'DefaultTheme';

	protected $twig = null;
	protected $assignation = array();

	public function __construct(){
		$this->initializeTwig()
			->prepareBaseAssignation();
		
	}

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

	/**
	 * Create a Twig Environment instance
	 */
	private function initializeTwig()
	{
		$cacheDir = RENZO_ROOT.'/cache/'.$this->themeDir.'/twig_cache';

		if (Kernel::getInstance()->isDebug()) {
			try {
				$fs = new Filesystem();
				$fs->remove(array($cacheDir));
			} catch (IOExceptionInterface $e) {
			    echo "An error occurred while deleting backend twig cache directory: ".$e->getPath();
			}
		}

		$loader = new \Twig_Loader_Filesystem(RENZO_ROOT.'/themes/'.$this->themeDir.'/Templates');
		$this->twig = new \Twig_Environment($loader, array(
		    'cache' => $cacheDir,
		));

		return $this;
	}
	/**
	 * @return \Twig_Environment
	 */
	public function getTwig()
	{
		return $this->twig;
	}

	public function prepareBaseAssignation()
	{
		$this->assignation = array(
			'head' => array(
				'baseUrl' => Kernel::getInstance()->getRequest()->getBaseUrl(),
				'resourcesUrl' => Kernel::getInstance()->getRequest()->getBaseUrl().'/themes/'.$this->themeDir.'/static/'
			)
		);

		return $this;
	}

	/**
	 * Return a Response with default backend 404 error page
	 * 
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function throw404()
	{
		return new Response(
		    $this->getTwig()->render('404.html.twig', $this->assignation),
		    Response::HTTP_NOT_FOUND,
		    array('content-type' => 'text/html')
		);
	}
}