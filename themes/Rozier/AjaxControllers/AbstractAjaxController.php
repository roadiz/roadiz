<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file AbstractAjaxController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\AjaxControllers;

use Themes\Rozier\RozierApp;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Extends common back-office controller, but add a request validation
 * to secure Ajax connexions.
 */
abstract class AbstractAjaxController extends RozierApp
{
    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param string                                   $method
     *
     * @return boolean | array  Return true if request is valid, else return error array
     */
    protected function validateRequest(Request $request, $method = 'POST')
    {
        if ($request->get('_action') == "") {
            return array(
                'statusCode'   => Response::HTTP_FORBIDDEN,
                'status'       => 'danger',
                'responseText' => 'Wrong request'
            );
        }
        if (!$this->getService('csrfProvider')
                ->isCsrfTokenValid(static::AJAX_TOKEN_INTENTION, $request->get('_token'))) {

            return array(
                'statusCode'   => Response::HTTP_FORBIDDEN,
                'status'       => 'danger',
                'responseText' => 'Bad token'
            );
        }
        if ($request->getMethod() != $method) {

            return array(
                'statusCode'   => Response::HTTP_FORBIDDEN,
                'status'       => 'danger',
                'responseText' => 'Bad method'
            );
        }

        return true;
    }
}
