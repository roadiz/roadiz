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

use RZ\Roadiz\CMS\Forms\NodeSource\NodeSourceType;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\HttpFoundation\Request;
use Themes\DefaultTheme\DefaultThemeApp;

/**
 * Frontend controller to handle Page node-type request.
 */
class PageController extends DefaultThemeApp
{

    /**
     * Default action for any Page node.
     *
     * @param Request $request
     * @param Node $node
     * @param Translation $translation
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Twig_Error_Runtime
     */
    public function indexAction(
        Request $request,
        Node $node = null,
        Translation $translation = null
    ) {
        $this->prepareThemeAssignation($node, $translation);

        if ($request->query->has('404') && $request->query->get('404') == true) {
            throw $this->createNotFoundException('This is a 404 page manually triggered.');
        }

        /*
         * You can add a NodeSourceType form to edit it
         * right into your front page.
         * Awesome isn’t it ?
         */

        return $this->render('pages/page.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     * @return null|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function handleEditForm(Request $request)
    {
        /*
         * Current page edition form
         */
        $form = $this->createForm(
            new NodeSourceType($this->node->getNodeType()),
            $this->nodeSource,
            [
                'controller' => $this,
                'entityManager' => $this->get('em'),
                'container' => $this->getContainer(),
                'withVirtual' => false,
                'withTitle' => false,
            ]
        );
        $form->handleRequest($request);
        if ($form->isValid()) {
            $this->get('em')->flush();
            return $this->redirect($this->generateUrl($this->nodeSource));
        }

        $this->assignation['form'] = $form->createView();

        return null;
    }
}
