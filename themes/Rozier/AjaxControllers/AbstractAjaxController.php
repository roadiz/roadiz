<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 *
 * @file AbstractAjaxController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\AjaxControllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfToken;
use Themes\Rozier\RozierApp;

/**
 * Extends common back-office controller, but add a request validation
 * to secure Ajax connexions.
 */
abstract class AbstractAjaxController extends RozierApp
{
    static $validMethods = ['post', 'get'];
    /**
     * @param Request $request
     * @param string  $method
     *
     * @return boolean | array  Return true if request is valid, else return error array
     */
    protected function validateRequest(Request $request, $method = 'POST', $requestCsrfToken = true)
    {
        if ($request->get('_action') == "") {
            return [
                'statusCode'   => Response::HTTP_FORBIDDEN,
                'status'       => 'danger',
                'responseText' => 'Wrong request'
            ];
        }

        if ($requestCsrfToken === true) {
            $token = new CsrfToken(static::AJAX_TOKEN_INTENTION, $request->get('_token'));
            if (!$this->getService('csrfTokenManager')->isTokenValid($token)) {
                return [
                    'statusCode'   => Response::HTTP_FORBIDDEN,
                    'status'       => 'danger',
                    'responseText' => 'Bad token'
                ];
            }
        }
        if (in_array(strtolower($method), static::$validMethods) &&
            strtolower($request->getMethod()) != strtolower($method)) {
            return [
                'statusCode'   => Response::HTTP_FORBIDDEN,
                'status'       => 'danger',
                'responseText' => 'Bad method'
            ];
        }

        return true;
    }
}
