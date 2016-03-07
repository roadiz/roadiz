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
 * @file NewslettersController.php
 * @author Maxime Constantinian
 */

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\CMS\Forms\Constraints\UniqueNodeName;
use RZ\Roadiz\Core\Entities\Newsletter;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;
use Themes\Rozier\RozierApp;
use Themes\Rozier\Traits\NodesSourcesTrait;
use Themes\Rozier\Traits\NodesTrait;

/**
 * Newsletter controller
 *
 * {@inheritdoc}
 */
class NewslettersController extends RozierApp
{
    use NodesSourcesTrait;
    use NodesTrait;

    public function listAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NEWSLETTERS');

        $translation = $this->getService('defaultTranslation');
        $listManager = $this->createEntityListManager(
            'RZ\Roadiz\Core\Entities\Newsletter',
            [],
            ["id" => "DESC"]
        );
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['newsletters'] = $listManager->getEntities();
        $this->assignation['nodeTypes'] = $this->getService('em')
             ->getRepository('RZ\Roadiz\Core\Entities\NodeType')
             ->findBy(['newsletterType' => true]);
        $this->assignation['translation'] = $translation;

        return $this->render('newsletters/list.html.twig', $this->assignation);
    }

    /**
     * Handle node creation pages.
     *
     * @param Request $request
     * @param int     $nodeTypeId
     * @param int     $translationId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request, $nodeTypeId, $translationId = null)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NEWSLETTERS');

        $type = $this->getService('em')
                     ->find('RZ\Roadiz\Core\Entities\NodeType', $nodeTypeId);

        $trans = $this->getService('defaultTranslation');

        if ($translationId !== null) {
            $trans = $this->getService('em')
                          ->find('RZ\Roadiz\Core\Entities\Translation', (int) $translationId);
        }

        if ($type !== null &&
            $trans !== null) {
            $form = $this->getService('formFactory')
                         ->createBuilder()
                         ->add('nodeName', 'text', [
                             'label' => 'nodeName',
                             'constraints' => [
                                 new NotBlank(),
                                 new UniqueNodeName([
                                     'entityManager' => $this->getService('em'),
                                 ]),
                             ],
                         ])
                ->getForm();
            $form->handleRequest($request);

            if ($form->isValid()) {
                try {
                    $data = $form->getData();
                    $node = $this->createNode($data, $type, $trans);

                    $newsletter = new Newsletter($node);
                    $newsletter->setStatus(Newsletter::DRAFT);

                    $this->getService('em')->persist($newsletter);
                    $this->getService('em')->flush();

                    $msg = $this->getTranslator()->trans(
                        'newsletter.%name%.created',
                        ['%name%' => $node->getNodeName()]
                    );
                    $this->publishConfirmMessage($request, $msg);

                    return $this->redirect($this->generateUrl(
                        'newslettersIndexPage'
                    ));
                } catch (EntityAlreadyExistsException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());

                    return $this->redirect($this->generateUrl(
                        'newsletterAddPage',
                        ['nodeTypeId' => $nodeTypeId, 'translationId' => $translationId]
                    ));
                }
            }

            $this->assignation['translation'] = $trans;
            $this->assignation['form'] = $form->createView();
            $this->assignation['type'] = $type;
            $this->assignation['nodeTypesCount'] = true;

            return $this->render('newsletters/add.html.twig', $this->assignation);
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return an edition form for requested newsletter.
     *
     * @param Request $request
     * @param int     $newsletterId
     * @param int     $translationId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $newsletterId, $translationId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NEWSLETTERS');

        $translation = $this->getService('em')
                            ->find('RZ\Roadiz\Core\Entities\Translation', (int) $translationId);

        if ($translation !== null) {
            /*
             * Here we need to directly select nodeSource
             * if not doctrine will grab a cache tag because of NodeTreeWidget
             * that is initialized before calling route method.
             */
            $newsletter = $this->getService('em')
                               ->find('RZ\Roadiz\Core\Entities\Newsletter', (int) $newsletterId);

            $source = $this->getService('em')
                           ->getRepository('RZ\Roadiz\Core\Entities\NodesSources')
                           ->findOneBy(['translation' => $translation, 'node' => $newsletter->getNode()]);

            if (null !== $source) {
                $node = $source->getNode();

                $this->assignation['translation'] = $translation;
                $this->assignation['available_translations'] = $newsletter->getNode()->getHandler()->getAvailableTranslations();
                $this->assignation['node'] = $node;
                $this->assignation['source'] = $source;
                $this->assignation['newsletterId'] = $newsletterId;

                /*
                 * Form
                 */
                $form = $this->buildEditSourceForm($node, $source);
                $form->handleRequest($request);

                if ($form->isValid()) {
                    $this->editNodeSource($form->getData(), $source);

                    $msg = $this->getTranslator()->trans('newsletter.%newsletter%.updated.%translation%', [
                        '%newsletter%' => $source->getNode()->getNodeName(),
                        '%translation%' => $source->getTranslation()->getName(),
                    ]);

                    $this->publishConfirmMessage($request, $msg);

                    return $this->redirect($this->generateUrl(
                        'newslettersEditPage',
                        ['newsletterId' => $newsletterId, 'translationId' => $translationId]
                    ));
                }

                $this->assignation['form'] = $form->createView();

                return $this->render('newsletters/edit.html.twig', $this->assignation);
            }
        }

        return $this->throw404();
    }
}
