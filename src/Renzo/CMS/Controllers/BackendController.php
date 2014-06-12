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
use Symfony\Component\HttpFoundation\Response;use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;


class BackendController {
	
	protected $twig = null;
	protected $assignation = array();

	public function __construct(){
		$this->initializeTwig()
			->prepareBaseAssignation();
		
	}

	/**
	 * Create a Twig Environment instance
	 */
	private function initializeTwig()
	{
		$cacheDir = RENZO_ROOT.'/cache/cms/twig_cache';

		if (Kernel::getInstance()->isDebug()) {
			try {
				$fs = new Filesystem();
				$fs->remove(array($cacheDir));
			} catch (IOExceptionInterface $e) {
			    echo "An error occurred while deleting backend twig cache directory: ".$e->getPath();
			}
		}

		$loader = new \Twig_Loader_Filesystem(RENZO_ROOT.'/src/Renzo/CMS/Resources/Templates');
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
				'resourcesUrl' => Kernel::getInstance()->getRequest()->getBaseUrl().'/static/'
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