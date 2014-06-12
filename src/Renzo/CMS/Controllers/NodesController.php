<?php 
namespace RZ\Renzo\CMS\Controllers;

use RZ\Renzo\Core\Kernel;
use Symfony\Component\HttpFoundation\Response;


class NodesController extends BackendController {
	
	public function indexAction()
	{
		$nodes = Kernel::getInstance()->em()
			->getRepository('RZ\Renzo\Core\Entities\Node')
			->findAll(array(), array('node_name'=>'ASC'));

		$this->assignation['nodes'] = $nodes;


		return new Response(
		    $this->getTwig()->render('nodes.html.twig', $this->assignation),
		    Response::HTTP_OK,
		    array('content-type' => 'text/html')
		);
	}

	public function noAction()
	{
		return $this->throw404();
	}

	public function editAction( $node_id, $translation_id = null )
	{
		$node = Kernel::getInstance()->em()
			->getRepository('RZ\Renzo\Core\Entities\Node')
			->findOneBy(array('id'=>(int)$node_id));

		$this->assignation['node'] = $node;

		return new Response(
		    $this->getTwig()->render('node/edit.html.twig', $this->assignation),
		    Response::HTTP_OK,
		    array('content-type' => 'text/html')
		);
	}
}