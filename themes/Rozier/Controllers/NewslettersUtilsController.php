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

/**
 * {@inheritdoc}
 */
class NewsletterUtilsController extends RozierApp
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
        $this->validateAccessForRole('ROLE_ACCESS_NEWSLETTER');

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
                        array("newsletterId" => $newNewsletter->getId())
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
                        array("nodeId" => $existingNewsletter->getId())
                    )
            );
        } finally {
            $response->prepare($request);
            return $response->send();
        }
    }
}
