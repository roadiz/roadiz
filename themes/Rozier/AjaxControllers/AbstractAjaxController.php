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

use RZ\Renzo\Core\Kernel;
use Themes\Rozier\RozierApp;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
        if ($request->get('_action') == "" ||
            $request->getMethod() != $method ||
            !$this->getKernel()
                ->getCsrfProvider()
                ->isCsrfTokenValid(static::AJAX_TOKEN_INTENTION, $request->get('_token'))) {

            return array(
                'statusCode'   => '403',
                'status'       => 'danger',
                'responseText' => 'Wrong request'
            );
        }

        return true;
    }
}
