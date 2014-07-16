<?php 
namespace Themes\Rozier\AjaxControllers;

use RZ\Renzo\Core\Kernel;
use Themes\Rozier\RozierApp;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;


class AbstractAjaxController extends RozierApp {

	/**
	 * 
	 * @param  Request $request [description]
	 * @return bool | array  Return true if request is valid, else return error array
	 */
	protected function validateRequest( Request $request )
	{
		if ($request->get('_action') == "" ||
			$request->getMethod() != 'POST' ||
			!static::$csrfProvider->isCsrfTokenValid(static::AJAX_TOKEN_INTENTION, $request->get('_token'))) {
			
			return array(
				'statusCode' => '403',
				'status' 	=> 'danger',
				'responseText' => 'Wrong request'
			);
		}
		return true;
	}
}