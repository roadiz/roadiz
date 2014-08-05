<?php 
namespace Themes\Rozier\AjaxControllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Tag;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Handlers\TagHandler;
use Themes\Rozier\AjaxControllers\AbstractAjaxController;

use Themes\Rozier\RozierApp;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;


class AjaxTagsController extends AbstractAjaxController
{
	
	/**
	 * Handle AJAX edition requests for Tag
	 * such as comming from tagtree widgets
	 * 
	 * @param  Request $request [description]
	 * @param  int  $tag_id [description]
	 * @return Symfony\Component\HttpFoundation\Response JSON response
	 */
	public function editAction( Request $request, $tag_id ) {

		/*
		 * Validate
		 */
		if (true !== $notValid = $this->validateRequest($request)) {
			return new Response(
				json_encode($notValid),
				Response::HTTP_OK,
				array('content-type' => 'application/javascript')
			);
		}

		$tag = Kernel::getInstance()->em()
			->find('RZ\Renzo\Core\Entities\Tag', (int)$tag_id);

		if ($tag !== null) {
			
			$responseArray = null;

			/*
			 * Get the right update method against "_action" parameter
			 */
			switch ($request->get('_action')) {
				case 'updatePosition':
					$responseArray = $this->updatePosition( $request->request->all(), $tag );
					break;
			}

			if ($responseArray === null) {
				$responseArray = array(
					'statusCode' => '200',
					'status' => 'success',
					'responseText' => ('Tag '.$tag_id.' edited ')
				);
			}
			
			return new Response(
				json_encode($responseArray),
				Response::HTTP_OK,
				array('content-type' => 'application/javascript')
			);
		}
		

		$responseArray = array(
			'statusCode' => '403',
			'status' 	=> 'danger',
			'responseText' => 'Tag '.$tag_id.' does not exists'
		);
		
		return new Response(
			json_encode($responseArray),
			Response::HTTP_OK,
			array('content-type' => 'application/javascript')
		);
	}

	/**
	 * [updatePosition description]
	 * @param  array  $parameters [description]
	 * @param  Tag   $tag       [description]
	 * @return [type]             [description]
	 */
	protected function updatePosition($parameters, Tag $tag)
	{
		/*
		 * First, we set the new parent
		 */
		$parent = null;

		if (!empty($parameters['newParent']) && 
			$parameters['newParent'] > 0) {

			$parent = Kernel::getInstance()->em()
				->find('RZ\Renzo\Core\Entities\Tag', (int)$parameters['newParent']);

			if ($parent !== null) {
				$tag->setParent($parent);
			}
		}
		elseif ($parameters['newParent'] == null) {
			$tag->setParent(null);
		}

		/*
		 * Then compute new position
		 */
		if (!empty($parameters['nextTagId']) && 
			$parameters['nextTagId'] > 0) {
			$nextTag = Kernel::getInstance()->em()
				->find('RZ\Renzo\Core\Entities\Tag', (int)$parameters['nextTagId']);
			if ($nextTag !== null) {
				$tag->setPosition($nextTag->getPosition() - 0.5);
			}
		}
		elseif (!empty($parameters['prevTagId']) && 
			$parameters['prevTagId'] > 0) {
			$prevTag = Kernel::getInstance()->em()
				->find('RZ\Renzo\Core\Entities\Tag', (int)$parameters['prevTagId']);
			if ($prevTag !== null) {
				$tag->setPosition($prevTag->getPosition() + 0.5);
			}
		}
		// Apply position update before cleaning
		Kernel::getInstance()->em()->flush();

		if ($parent !== null) {
			$parent->getHandler()->cleanChildrenPositions();
		}
		else {
			TagHandler::cleanRootTagsPositions();
		}
	}
}