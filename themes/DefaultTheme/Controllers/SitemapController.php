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
namespace Themes\DefaultTheme\Controllers;

use RZ\Roadiz\Core\Entities\NodeType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\DefaultTheme\DefaultThemeApp;

/**
 * Class SitemapController
 * @package Themes\DefaultTheme\Controllers
 */
class SitemapController extends DefaultThemeApp
{
    /**
     * @param Request $request
     * @param string  $_locale
     *
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function sitemapAction(
        Request $request,
        $_locale = 'fr'
    ) {
        $this->prepareThemeAssignation(null, $this->bindLocaleFromRoute($request, $_locale));

        $nodeTypes = $this->get('em')
            ->getRepository(NodeType::class)
            ->findBy([
                'reachable' => true
            ]);

        /*
         * Add your own nodes grouped by their type.
         */
        $this->assignation['pages'] = $this->get('nodeSourceApi')
            ->getBy([
                'node.nodeType' => $nodeTypes,
                'node.visible' => true,
            ]);

        $response = new Response(
            trim($this->getTwig()->render('sitemap/sitemap.xml.twig', $this->assignation)),
            Response::HTTP_OK,
            ['content-type' => 'application/xml']
        );
        
        $this->makeResponseCachable($request, $response, 60);
        return $response;
    }
}
