<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 *
 * @file AjaxSessionMessages.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\AjaxControllers;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

use RZ\Renzo\Core\Kernel;

/**
 * AjaxSessionMessages.
 */
class AjaxSessionMessages extends AbstractAjaxController
{
    /**
     * @param Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response JSON response
     */
    public function getMessagesAction(Request $request)
    {
        /*
         * Validate
         */
        if (true !== $notValid = $this->validateRequest($request, 'GET')) {
            return new Response(
                json_encode($notValid),
                Response::HTTP_FORBIDDEN,
                array('content-type' => 'application/javascript')
            );
        }

        $this->validateAccessForRole('ROLE_BACKEND_USER');

        $responseArray = array(
            'statusCode' => Response::HTTP_OK,
            'status'    => 'success',
            'messages' => $request->getSession()->getFlashBag()->all()
        );

        return new Response(
            json_encode($responseArray),
            Response::HTTP_OK,
            array('content-type' => 'application/javascript')
        );
    }
}

?>
