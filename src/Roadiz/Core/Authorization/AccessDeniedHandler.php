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
 * @file AccessDeniedHandler.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Authorization;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

/**
 * This is used by the ExceptionListener to translate an AccessDeniedException
 * to a Response object.
 */
class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    protected $logger;
    protected $urlGenerator;

    /**
     * @param UrlGenerator $urlGenerator
     * @param LoggerInterface $logger
     */
    public function __construct(UrlGenerator $urlGenerator, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Handles an access denied failure redirecting to home page
     *
     * @param Request               $request
     * @param AccessDeniedException $accessDeniedException
     *
     * @return Response may return null
     */
    public function handle(Request $request, AccessDeniedException $accessDeniedException)
    {
        $this->logger->error('User tried to access: ' . $request->getUri());

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(
                [
                    'message' => $accessDeniedException->getMessage(),
                    'trace' => $accessDeniedException->getTraceAsString(),
                    'exception' => get_class($accessDeniedException),
                ],
                Response::HTTP_SERVICE_UNAVAILABLE
            );
        } else {
            $response = new RedirectResponse($request->getSchemeAndHttpHost());
            $response->setStatusCode(Response::HTTP_FORBIDDEN);

            return $response;
        }
    }
}
