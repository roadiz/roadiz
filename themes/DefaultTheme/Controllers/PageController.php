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
 * @file PageController.php
 * @author Ambroise Maupate
 */
namespace Themes\DefaultTheme\Controllers;

use JMS\Serializer\SerializationContext;
use RZ\Roadiz\CMS\Forms\NodeSource\NodeSourceType;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Themes\DefaultTheme\DefaultThemeApp;

/**
 * Frontend controller to handle Page node-type request.
 */
class PageController extends DefaultThemeApp
{
    /**
     * @param Request $request
     * @param string $_locale
     *
     * @return JsonResponse
     */
    public function embedAction(Request $request, $name, $_locale = 'en')
    {
        $translation = $this->bindLocaleFromRoute($request, $_locale);
        $this->prepareThemeAssignation(null, $translation);

        return new JsonResponse([
            'message' => 'Hello '. $name. '!'
        ]);
    }

    /**
     * Default action for any Page node.
     *
     * @param Request     $request
     * @param Node        $node
     * @param Translation $translation
     * @param string      $_format
     *
     * @return Response
     */
    public function indexAction(
        Request $request,
        Node $node = null,
        Translation $translation = null,
        $_format = 'html'
    ) {
        $this->prepareThemeAssignation($node, $translation);


        if ($request->query->has('404') && $request->query->get('404') == true) {
            throw $this->createNotFoundException('This is a 404 page manually triggered via ' . ResourceNotFoundException::class);
        }

        if ($request->query->has('not-found') && $request->query->get('not-found') == true) {
            throw new NotFoundHttpException('This is a 404 page manually triggered via '. NotFoundHttpException::class);
        }

        /*
         * You can add a NodeSourceType form to edit it
         * right into your front page.
         * Awesome isn’t it ?
         */
        if ($request->getRequestFormat() === 'json') {
            $response = new JsonResponse(
                $this->get('serializer')->serialize(
                    $this->nodeSource,
                    'json',
                    SerializationContext::create()->setGroups(['nodes_sources', 'urls'])
                ),
                Response::HTTP_OK,
                [],
                true
            );
        } else {
            $response = $this->render('pages/page.html.twig', $this->assignation);
        }

        if ($this->getNode()->getTtl() > 0) {
            return $this->makeResponseCachable($request, $response, $this->getNode()->getTtl());
        }
        return $response;
    }

    /**
     * @param Request $request
     *
     * @return null|RedirectResponse
     */
    protected function handleEditForm(Request $request)
    {
        /*
         * Current page edition form
         */
        $form = $this->createForm(
            NodeSourceType::class,
            $this->nodeSource,
            [
                'class' => $this->node->getNodeType()->getSourceEntityFullQualifiedClassName(),
                'nodeType' => $this->node->getNodeType(),
                'withVirtual' => false,
                'withTitle' => false,
            ]
        );
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->get('em')->flush();
            return $this->redirect($this->generateUrl($this->nodeSource));
        }

        $this->assignation['form'] = $form->createView();

        return null;
    }
}
