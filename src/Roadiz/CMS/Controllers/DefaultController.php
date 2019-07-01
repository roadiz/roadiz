<?php
/**
 * Copyright (c) 2019. Ambroise Maupate and Julien Blanchet
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
 */

namespace RZ\Roadiz\CMS\Controllers;

use JMS\Serializer\SerializationContext;
use RZ\Roadiz\Core\ContainerAwareInterface;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Theme;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class DefaultController extends FrontendController
{
    /**
     * @param Request          $request
     * @param Node|null        $node
     * @param Translation|null $translation
     * @param string           $_format
     * @param Theme|null       $theme
     *
     * @return JsonResponse|Response
     * @throws \Twig_Error_Runtime
     */
    public function indexAction(
        Request $request,
        Node $node = null,
        Translation $translation = null,
        $_format = 'html',
        Theme $theme = null
    ) {
        /*
         * Fetch all assignation from current Theme.
         */
        if (null !== $theme) {
            $className = $theme->getClassName();
            $usedTheme = new $className();
            if ($usedTheme instanceof ContainerAwareInterface) {
                $usedTheme->setContainer($this->getContainer());
            }
            if ($usedTheme instanceof FrontendController) {
                $usedTheme->__init();
                $usedTheme->prepareThemeAssignation($node, $translation);
                $this->assignation = array_merge($this->assignation, $usedTheme->getAssignation());
                $this->node = $usedTheme->getNode();
                $this->nodeSource = $usedTheme->getNodeSource();
                $this->translation = $usedTheme->getTranslation();
            } else {
                $this->prepareThemeAssignation($node, $translation);
            }
        } else {
            $this->prepareThemeAssignation($node, $translation);
        }

        if ($request->getRequestFormat() === 'json') {
            $response = new JsonResponse(
                $this->get('serializer')->serialize(
                    $this->getNodeSource(),
                    'json',
                    SerializationContext::create()->setGroups(['nodes_sources', 'urls'])
                ),
                Response::HTTP_OK,
                [],
                true
            );
        }
        $response = $this->render('pages/' . strtolower($node->getNodeType()->getName()) . '.html.twig', $this->assignation, null, '/');

        if ($this->getNode()->getTtl() > 0) {
            return $this->makeResponseCachable($request, $response, $this->getNode()->getTtl());
        }
        return $response;
    }
}
