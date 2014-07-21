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

use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Utils\StringHandler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Exception\ResourceNotFoundException;

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


	protected static $specificNodesControllers = array(
		'home',
		// Put here your node which need a specific controller
		// instead of a node-type controller
	);

	protected $node = null;
	protected $translation = null;

	/**
	 * Default action for any node URL
	 * @param  Symfony\Component\HttpFoundation\Request
	 * @param  RZ\Renzo\Core\Entities\Node $node Requested node for given URL
	 * @param  RZ\Renzo\Core\Entities\Translation $translation
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function indexAction( Request $request, Node $node = null, Translation $translation = null)
	{
		$this->storeNodeAndTranslation($node, $translation);

		//	Main node based routing method
		return $this->handle( $request );
	}

	/**
	 * Default action for default URL (homepage)
	 * @param  Symfony\Component\HttpFoundation\Request
	 * @param  RZ\Renzo\Core\Entities\Node $node Requested node for given URL
	 * @param  RZ\Renzo\Core\Entities\Translation $translation
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function homeAction( Request $request, Node $node = null, Translation $translation = null)
	{
		$this->storeNodeAndTranslation($node, $translation);

		return new Response(
			$this->getTwig()->render('home.html.twig', $this->assignation),
			Response::HTTP_OK,
			array('content-type' => 'text/html')
		);
	}

	/**
	 * 
	 * @param  RZ\Renzo\Core\Entities\Node $node
	 * @param  RZ\Renzo\Core\Entities\Translation $translation
	 */
	public function storeNodeAndTranslation( Node $node = null, Translation $translation = null )
	{
		$this->node = $node;
		$this->translation = $translation;

		$this->assignation['node'] = $node;
		$this->assignation['translation'] = $translation;
	}

	/**
	 * Handle node based routing
	 * @param  Symfony\Component\HttpFoundation\Request
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	protected function handle( Request $request )
	{	
		$currentClass = get_class($this);
        $refl = new \ReflectionClass($currentClass);
        $namespace = $refl->getNamespaceName();

        if ($this->getRequestedNode() !== null) {

			$nodeController = 		$namespace . '\\' . StringHandler::classify($this->getRequestedNode()->getNodeName()) . 'Controller';
			$nodeTypeController = 	$namespace . '\\' . StringHandler::classify($this->getRequestedNode()->getNodeType()->getName()) . 'Controller';

			if (in_array($this->getRequestedNode()->getNodeName(), static::$specificNodesControllers) && 
				class_exists($nodeController) && 
				method_exists($nodeController, 'indexAction')) {
				
				$ctrl = new $nodeController();
				return $ctrl->indexAction($request, $this->getRequestedNode(), $this->getRequestedTranslation());
			}
			elseif (class_exists($nodeTypeController) && 
				method_exists($nodeTypeController, 'indexAction')) {

				$ctrl = new $nodeTypeController();
				return $ctrl->indexAction($request, $this->getRequestedNode(), $this->getRequestedTranslation());
			}
			else {
				throw new ResourceNotFoundException("No front-end controller found for '".$this->getRequestedNode()->getNodeName()."' node. Need a ".$nodeController." or ".$nodeTypeController." controller.");
			}
        }
		throw new ResourceNotFoundException("No front-end controller found");
	}


	/**
	 * 
	 * @return RZ\Renzo\Core\Entities\Node
	 */
	public function getRequestedNode()
	{
		return $this->node;
	}
	/**
	 * 
	 * @return RZ\Renzo\Core\Entities\Translation
	 */
	public function getRequestedTranslation()
	{
		return $this->translation;
	}
}