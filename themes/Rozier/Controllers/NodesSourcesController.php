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
 * @file NodesController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;

use Themes\Rozier\RozierApp;
use Themes\Rozier\Traits\NodesSourcesTrait;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Translation\Translator;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Nodes sources controller.
 *
 * {@inheritdoc}
 */
class NodesSourcesController extends RozierApp
{

    use NodesSourcesTrait;

    /**
     * Return an edition form for requested node.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $nodeId
     * @param int                                      $translationId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function editSourceAction(Request $request, $nodeId, $translationId)
    {
        //$this->validateAccessForRole('ROLE_ACCESS_NODES');

        $node = $this->getService('em')
            ->find('RZ\Roadiz\Core\Entities\Node', (int) $nodeId);

        $this->getService('em')->refresh($node);

        $user = $this->getService("securityContext")->getToken()->getUser();

        $parents = $node->getHandler()->getParents();

        $isNewsletterFriend = $node->getHandler()->isRelatedToNewsletter();

        if (!((!$isNewsletterFriend
                && $this->getService('securityContext')->isGranted('ROLE_ACCESS_NODES')
                && ($user->getChroot() == null
                    || in_array($user->getChroot(), $parents, true)))
            || ($isNewsletterFriend
                && $this->getService('securityContext')->isGranted('ROLE_ACCESS_NEWSLETTERS')))) {
            throw new AccessDeniedException("You don't have access to this page");
        }


        $translation = $this->getService('em')
                ->find('RZ\Roadiz\Core\Entities\Translation', (int) $translationId);

        if ($translation !== null) {
            /*
             * Here we need to directly select nodeSource
             * if not doctrine will grab a cache tag because of NodeTreeWidget
             * that is initialized before calling route method.
             */
            $gnode = $this->getService('em')
                ->find('RZ\Roadiz\Core\Entities\Node', (int) $nodeId);

            $source = $this->getService('em')
                ->getRepository('RZ\Roadiz\Core\Entities\NodesSources')
                ->findOneBy(['translation'=>$translation, 'node'=>$gnode]);

            if (null !== $source) {
                $node = $source->getNode();

                $this->assignation['translation'] = $translation;
                $this->assignation['available_translations'] = $gnode->getHandler()->getAvailableTranslations();
                $this->assignation['node'] = $node;
                $this->assignation['source'] = $source;

                /*
                 * Form
                 */
                $form = $this->buildEditSourceForm($node, $source);
                $form->handleRequest();

                if ($form->isValid()) {
                    $this->editNodeSource($form->getData(), $source);

                    $msg = $this->getTranslator()->trans('node_source.%node_source%.updated.%translation%', [
                        '%node_source%'=>$source->getNode()->getNodeName(),
                        '%translation%'=>$source->getTranslation()->getName()
                    ]);

                    $this->publishConfirmMessage($request, $msg, $source);

                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'nodesEditSourcePage',
                            ['nodeId' => $node->getId(), 'translationId'=>$translation->getId()]
                        )
                    );
                    $response->prepare($request);

                    return $response->send();
                }

                $this->assignation['form'] = $form->createView();

                return new Response(
                    $this->getTwig()->render('nodes/editSource.html.twig', $this->assignation),
                    Response::HTTP_OK,
                    ['content-type' => 'text/html']
                );
            }
        }
        return $this->throw404();
    }

    /**
    * Return an remove form for requested nodeSource.
    *
    * @param Symfony\Component\HttpFoundation\Request $request
    * @param int                                      $nodeSourceId
    *
    * @return Symfony\Component\HttpFoundation\Response
    */
    public function removeAction(Request $request, $nodeSourceId)
    {
        $builder = $this->getService('formFactory')
            ->createBuilder('form')
            ->add('nodeId', 'hidden', [
                'data' => $nodeSourceId,
                'constraints' => [
                    new NotBlank()
                ]
            ]);

        $form = $builder->getForm();

        $form->handleRequest();

        $ns = $this->getService("em")->find("RZ\Roadiz\Core\Entities\NodesSources", $nodeSourceId);

        if ($form->isValid()) {
            $node = $ns->getNode();
            if ($node->getNodeSources()->count() <= 1) {
                $msg = $this->getTranslator()->trans('node_source.%node_source%.%translation%.cant.deleted', [
                    '%node_source%'=>$node->getNodeName(),
                    '%translation%'=>$ns->getTranslation()->getName()
                ]);

                $this->publishErrorMessage($request, $msg);
            } else {
                $this->getService("em")->remove($ns);
                $this->getService("em")->flush();

                $ns = $node->getNodeSources()->first();

                $msg = $this->getTranslator()->trans('node_source.%node_source%.deleted.%translation%', [
                    '%node_source%'=>$node->getNodeName(),
                    '%translation%'=>$ns->getTranslation()->getName()
                ]);

                $this->publishConfirmMessage($request, $msg);
            }
            $response = new RedirectResponse(
                $this->getService('urlGenerator')->generate(
                    'nodesEditSourcePage',
                    ['nodeId' => $node->getId(), "translationId" => $ns->getTranslation()->getId()]
                )
            );

            $response->prepare($request);

            return $response->send();
        }

        $this->assignation["nodeSource"] = $ns;
        $this->assignation['form'] = $form->createView();

        return new Response(
            $this->getTwig()->render('nodes/deleteSource.html.twig', $this->assignation),
            Response::HTTP_OK,
            ['content-type' => 'text/html']
        );
    }
}
