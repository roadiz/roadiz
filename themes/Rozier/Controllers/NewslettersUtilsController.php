<?php
/*
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
 * @file NodesUtilsController.php
 * @author Thomas Aufresne
 */

namespace Themes\Rozier\Controllers;

use Themes\Rozier\RozierApp;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

use \InlineStyle\InlineStyle;
/**
 * {@inheritdoc}
 */
class NewslettersUtilsController extends RozierApp
{

    /**
     * Duplicate node by ID
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $nodeId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function duplicateAction(Request $request, $newsletterId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NEWSLETTERS');

        $translation = $this->getService('em')
                            ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                            ->findDefault();

        try {
            $existingNewsletter = $this->getService('em')
                                  ->find('RZ\Roadiz\Core\Entities\Newsletter', (int) $newsletterId);

            $newNewsletter = $existingNewsletter->getHandler()->duplicate();

            $msg = $this->getTranslator()->trans("duplicated.newsletter.%name%", array(
                '%name%' => $existingNewsletter->getNode()->getNodeName()
            ));

            $this->publishConfirmMessage($request, $msg);

            $response = new RedirectResponse(
                $this->getService('urlGenerator')
                    ->generate(
                        'newslettersEditPage',
                        array(
                            "newsletterId" => $newNewsletter->getId(),
                            "translationId" => $translation->getId()
                        )
                    )
            );

        } catch (\Exception $e) {
            $request->getSession()->getFlashBag()->add(
                'error',
                $this->getTranslator()->trans("impossible.duplicate.newsletter.%name%", array(
                    '%name%' => $existingNewsletter->getNode()->getNodeName()
                ))
            );
            $request->getSession()->getFlashBag()->add('error', $e->getMessage());

            $response = new RedirectResponse(
                $this->getService('urlGenerator')
                     ->generate(
                        'newslettersEditPage',
                        array(
                            "newsletterId" => $existingNewsletter->getId(),
                            "translationId" => $translation->getId()
                        )
                     )
            );
        }
        $response->prepare($request);
        return $response->send();
    }

    private function getBaseNamespace()
    {
        $theme = $this->getService("em")
            ->getRepository("RZ\Roadiz\Core\Entities\Theme")
            ->findFirstAvailableNonStaticFrontend();
        $baseNamespace = explode("\\", $theme->getClassName());
        $baseNamespace = array_reverse($baseNamespace);
        unset($baseNamespace[0]);
        $baseNamespace = array_reverse($baseNamespace);
        $baseNamespace = implode("\\", $baseNamespace);
        return $baseNamespace;
    }

    private function getNewsletterHTML(Request $request, $newsletter)
    {
        $baseNamespace = $this->getBaseNamespace();

        $classname = $baseNamespace
            . "\NewslettersController\\"
            . $newsletter->getNode()->getNodeType()->getName()
            . "Controller";

        $this->getService('twig.loaderFileSystem')->prependPath($classname::getViewsFolder());

        $front = new $classname();
        $front->setKernel($this->kernel);
        $front->prepareBaseAssignation();
        return $front->makeHtml($request, $newsletter);
    }

    public function previewAction(Request $request, $newsletterId)
    {
        $newsletter = $this->getService("em")->find(
            "RZ\Roadiz\Core\Entities\Newsletter",
            $newsletterId
        );

        return new Response(
            $this->getNewsletterHTML($request, $newsletter),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    public function exportAction(Request $request, $newsletterId, $inline)
    {
        $newsletter = $this->getService("em")->find(
            "RZ\Roadiz\Core\Entities\Newsletter",
            $newsletterId
        );

        //Get all css link in the newsletter

        $content = $this->getNewsletterHTML($request, $newsletter);
        preg_match_all('/href="([^"]+\.css)"/', $content, $out);

        //Concat all cssfile in one string

        $cssContent = "";
        foreach ($out[1] as $css) {
            $cssContent .= file_get_contents(ROADIZ_ROOT.$css) . PHP_EOL;
        }

        if ($inline != 0) {

            // inline newsletter html with css

            $htmldoc = new InlineStyle($content);
            $htmldoc->applyStylesheet($cssContent);
            $htmldoc = $htmldoc->getHtml();
        } else {

            // Remove all link element and add style balise with all css file content

            $content = preg_replace('/<link[^>]+\/>/', '', $content);
            $htmldoc = str_replace(
                "</head>",
                "<style>\n" . $cssContent . "</style>\n</head>",
                $content
            );
        }

        // Generate response
        $response = new Response();

        // Set headers
        $response->headers->set('Content-type', "text/html");
        $response->headers->set('Content-Disposition', 'attachment; filename= "' . $newsletter->getNode()->getNodeName() . '";');

        $response->setContent($htmldoc);

        return $response;
    }
}
