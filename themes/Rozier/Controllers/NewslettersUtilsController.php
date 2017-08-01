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

use InlineStyle\InlineStyle;
use RZ\Roadiz\Core\Entities\Newsletter;
use RZ\Roadiz\Core\Handlers\NewsletterHandler;
use RZ\Roadiz\Utils\DomHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\Rozier\RozierApp;

/**
 * {@inheritdoc}
 */
class NewslettersUtilsController extends RozierApp
{

    /**
     * Duplicate node by ID.
     *
     * @param Request $request
     * @param int     $newsletterId
     *
     * @return Response
     */
    public function duplicateAction(Request $request, $newsletterId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NEWSLETTERS');

        $translation = $this->get('defaultTranslation');

        try {
            /** @var Newsletter $existingNewsletter */
            $existingNewsletter = $this->get('em')->find('RZ\Roadiz\Core\Entities\Newsletter', (int) $newsletterId);
            /** @var NewsletterHandler $handler */
            $handler = $this->get('newsletter.handler');
            $handler->setNewsletter($existingNewsletter);

            $newNewsletter = $handler->duplicate();

            $msg = $this->getTranslator()->trans("duplicated.newsletter.%name%", [
                '%name%' => $existingNewsletter->getNode()->getNodeName(),
            ]);

            $this->publishConfirmMessage($request, $msg);

            return $this->redirect($this->get('urlGenerator')
                                            ->generate(
                                                'newslettersEditPage',
                                                [
                                                    "newsletterId" => $newNewsletter->getId(),
                                                    "translationId" => $translation->getId(),
                                                ]
                                            ));
        } catch (\Exception $e) {
            $request->getSession()->getFlashBag()->add(
                'error',
                $this->getTranslator()->trans("impossible.duplicate.newsletter.%name%", [
                    '%name%' => $existingNewsletter->getNode()->getNodeName(),
                ])
            );
            $request->getSession()->getFlashBag()->add('error', $e->getMessage());

            return $this->redirect($this->get('urlGenerator')
                                            ->generate(
                                                'newslettersEditPage',
                                                [
                                                    "newsletterId" => $existingNewsletter->getId(),
                                                    "translationId" => $translation->getId(),
                                                ]
                                            ));
        }
    }

    private function getBaseNamespace()
    {
        // get first not static frontend
        $theme = $this->get("em")
                      ->getRepository("RZ\Roadiz\Core\Entities\Theme")
                      ->findFirstAvailableNonStaticFrontend();

        $baseNamespace = explode("\\", $theme->getClassName());

        // remove last elem of the array
        array_pop($baseNamespace);

        $baseNamespace = implode("\\", $baseNamespace);
        return $baseNamespace;
    }

    private function getNewsletterHTML(Request $request, $newsletter)
    {
        $baseNamespace = $this->getBaseNamespace();

        // make namespace of the newsletter from the default dynamic theme namespace and newsletter notetype
        $classname = $baseNamespace
        . "\NewsletterControllers\\"
        . $newsletter->getNode()->getNodeType()->getName()
        . "Controller";
        // force the twig path
        $this->get('twig.loaderFileSystem')->prependPath($classname::getViewsFolder());

        // get html from the controller
        $front = new $classname();
        $front->setContainer($this->getContainer());
        $front->prepareBaseAssignation();
        return $front->makeHtml($request, $newsletter);
    }

    /**
     * Preview a newsletter
     *
     * @param Request $request
     * @param int     $newsletterId
     *
     * @return Response
     */
    public function previewAction(Request $request, $newsletterId)
    {
        $newsletter = $this->get("em")->find(
            "RZ\Roadiz\Core\Entities\Newsletter",
            $newsletterId
        );

        return new Response(
            $this->getNewsletterHTML($request, $newsletter),
            Response::HTTP_OK,
            ['content-type' => 'text/html']
        );
    }

    /**
     * Export the newsletter in HTML with or without inline CSS
     *
     * @param Request $request
     * @param int     $newsletterId
     * @param int     $inline
     *
     * @return Response
     */
    public function exportAction(Request $request, $newsletterId, $inline)
    {
        $newsletter = $this->get("em")->find(
            "RZ\Roadiz\Core\Entities\Newsletter",
            $newsletterId
        );

        $filename = $newsletter->getNode()->getNodeName();
        $content = $this->getNewsletterHTML($request, $newsletter);

        // Get all css link in the newsletter
        $cssContent = DomHandler::getExternalStyles($content);

        if ((boolean) $inline === true) {
            // inline newsletter html with css

            $htmldoc = new InlineStyle($content);
            $htmldoc->applyStylesheet($cssContent);
            $htmldoc = $htmldoc->getHtml();

            $filename .= "-inlined";

            $content = $htmldoc;
        }

        // Remove all link element and add style balise with all css file content
        $htmldoc = DomHandler::replaceExternalStylesheetsWithStyle($content, $cssContent);

        // Generate response
        $response = new Response();

        // Set headers
        $response->headers->set('Content-type', "text/html");
        $response->headers->set('Content-Disposition', 'attachment; filename= "' . $filename . '.html";');

        $response->setContent($htmldoc);

        return $response;
    }
}
