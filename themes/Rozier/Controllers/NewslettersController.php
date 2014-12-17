<?php
/**
 * Copyright © 2014, REZO ZERO
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
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file NewsletterController.php
 * @copyright REZO ZERO 2014
 * @author Maxime Constantinian
 */

namespace Themes\Rozier\Controllers;

use Themes\Rozier\RozierApp;

use RZ\Roadiz\Core\ListManagers\EntityListManager;
use RZ\Roadiz\Core\Entities\Newsletter;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Nodes controller
 *
 * {@inheritdoc}
 */
class NewslettersController extends RozierApp
{
    public function listAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NEWSLETTERS');

        $translation = $this->getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Translation')
            ->findDefault();
        $listManager = new EntityListManager(
            $request,
            $this->getService('em'),
            'RZ\Roadiz\Core\Entities\Newsletter'
        );
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['newsletter'] = $listManager->getEntities();
        $this->assignation['nodeTypes'] = $this->getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\NodeType')
            ->findBy(array('newsletterType' => true));
        $this->assignation['translation'] = $translation;

        return new Response(
            $this->getTwig()->render('newsletters/list.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * Handle node creation pages.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $nodeTypeId
     * @param int                                      $translationId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request, $nodeTypeId, $translationId = null)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NEWSLETTERS');

        $type = $this->getService('em')
            ->find('RZ\Roadiz\Core\Entities\NodeType', $nodeTypeId);

        $translation = $this->getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Translation')
            ->findDefault();

        if ($translationId !== null) {
            $translation = $this->getService('em')
                ->find('RZ\Roadiz\Core\Entities\Translation', (int) $translationId);
        }

        if ($type !== null &&
            $translation !== null) {
            $form = $this->getService('formFactory')
                ->createBuilder()
                ->add('nodeName', 'text', array(
                    'label' => $this->getTranslator()->trans('nodeName'),
                    'constraints' => array(
                        new NotBlank()
                    )
                ))
                ->getForm();
            $form->handleRequest();

            if ($form->isValid()) {
                try {
                    $node = $this->createNode($form->getData(), $type, $translation);

                    $newsletter = new Newsletter($node);

                    $this->getService('em')->persist($newsletter);
                    $this->getService('em')->flush();

                    $msg = $this->getTranslator()->trans(
                        'newsletter.%name%.created',
                        array('%name%'=>$node->getNodeName())
                    );
                    $this->publishConfirmMessage($request, $msg);

                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'newsletterEditPage',
                            array('nodeId' => $node->getId())
                        )
                    );
                    $response->prepare($request);

                    return $response->send();
                } catch (EntityAlreadyExistsException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());

                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'newsletterAddPage',
                            array('nodeTypeId' => $nodeTypeId, 'translationId' => $translationId)
                        )
                    );
                    $response->prepare($request);

                    return $response->send();
                }
            }

            $this->assignation['translation'] = $translation;
            $this->assignation['form'] = $form->createView();
            $this->assignation['type'] = $type;
            $this->assignation['nodeTypesCount'] = true;

            return new Response(
                $this->getTwig()->render('newsletters/add.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }
}