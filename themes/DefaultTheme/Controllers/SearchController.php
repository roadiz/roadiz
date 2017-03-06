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
 * @file SearchController.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace Themes\DefaultTheme\Controllers;

use RZ\Roadiz\Core\Entities\NodesSources;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Themes\DefaultTheme\DefaultThemeApp;

class SearchController extends DefaultThemeApp
{

    /**
     * Default action for searching a Page source.
     *
     * @param Request $request
     * @param string $_locale
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function defaultAction(
        Request $request,
        $_locale = "en"
    ) {
        $translation = $this->bindLocaleFromRoute($request, $_locale);
        $this->prepareThemeAssignation(null, $translation);

        if (!$request->query->has('query') || $request->query->get('query') == '') {
            throw new ResourceNotFoundException();
        }

        /*
         * Use simple search over title and meta fields.
         */
        /** @var NodesSources[] $nodeSources */
        $nodeSources = $this->get('nodeSourceApi')->searchBy(
            $request->query->get('query'),
            10,
            [
                $this->themeContainer['typePage']
            ]
        );

        $this->assignation['nodeSources'] = $nodeSources;
        $this->assignation['query'] = $request->query->get('query');

        return $this->render('pages/search.html.twig', $this->assignation);
    }
}
