<?php
/**
 * Copyright (c) 2017. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file RedirectionController.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\CMS\Controllers;

use RZ\Roadiz\Core\Entities\Redirection;
use RZ\Roadiz\Core\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class RedirectionController
 * @package RZ\Roadiz\CMS\Controllers
 */
class RedirectionController extends AppController
{
    /**
     * @param Request $request
     * @param Redirection $redirection
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirectAction(Request $request, Redirection $redirection)
    {
        if (null !== $redirection->getRedirectNodeSource()) {
            return $this->redirect($this->generateUrl($redirection->getRedirectNodeSource()), $redirection->getType());
        }

        if (strlen($redirection->getRedirectUri()) > 0) {
            return $this->redirect($redirection->getRedirectUri(), $redirection->getType());
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Redirects to another route with the given name.
     *
     * The response status code is 302 if the permanent parameter is false (default),
     * and 301 if the redirection is permanent.
     *
     * In case the route name is empty, the status code will be 404 when permanent is false
     * and 410 otherwise.
     *
     * @param Request    $request          The request instance
     * @param string     $route            The route name to redirect to
     * @param bool       $permanent        Whether the redirection is permanent
     * @param bool|array $ignoreAttributes Whether to ignore attributes or an array of attributes to ignore
     *
     * @return RedirectResponse A Response instance
     *
     * @throws HttpException In case the route name is empty
     */
    public function redirectToRouteAction(Request $request, $route, $permanent = false, $ignoreAttributes = false)
    {
        if ('' == $route) {
            throw new HttpException($permanent ? 410 : 404);
        }
        $attributes = array();
        if (false === $ignoreAttributes || is_array($ignoreAttributes)) {
            $attributes = $request->attributes->get('_route_params');
            unset($attributes['route'], $attributes['permanent'], $attributes['ignoreAttributes']);
            if ($ignoreAttributes) {
                $attributes = array_diff_key($attributes, array_flip($ignoreAttributes));
            }
        }
        return new RedirectResponse($this->generateUrl($route, $attributes, UrlGeneratorInterface::ABSOLUTE_URL), $permanent ? 301 : 302);
    }
}
