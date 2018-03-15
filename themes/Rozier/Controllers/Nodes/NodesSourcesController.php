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
 * @file NodesController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers\Nodes;

use RZ\Roadiz\CMS\Forms\NodeSource\NodeSourceType;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Events\FilterNodeEvent;
use RZ\Roadiz\Core\Events\FilterNodesSourcesEvent;
use RZ\Roadiz\Core\Events\NodeEvents;
use RZ\Roadiz\Core\Events\NodesSourcesEvents;
use RZ\Roadiz\Core\Handlers\NodeHandler;
use RZ\Roadiz\Utils\Node\NodeNameChecker;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Validator\Constraints\NotBlank;
use Themes\Rozier\RozierApp;

/**
 * Nodes sources controller.
 *
 * {@inheritdoc}
 */
class NodesSourcesController extends RozierApp
{
    /**
     * Return an edition form for requested node.
     *
     * @param Request $request
     * @param int     $nodeId
     * @param int     $translationId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editSourceAction(Request $request, $nodeId, $translationId)
    {
        $this->validateNodeAccessForRole('ROLE_ACCESS_NODES', $nodeId);

        /** @var Translation $translation */
        $translation = $this->get('em')
                            ->find(Translation::class, (int) $translationId);
        /*
         * Here we need to directly select nodeSource
         * if not doctrine will grab a cache tag because of NodeTreeWidget
         * that is initialized before calling route method.
         */
        /** @var Node $gnode */
        $gnode = $this->get('em')->find(Node::class, (int) $nodeId);

        if ($translation !== null && $gnode !== null) {
            /** @var NodesSources $source */
            $source = $this->get('em')
                           ->getRepository(NodesSources::class)
                           ->setDisplayingAllNodesStatuses(true)
                           ->setDisplayingNotPublishedNodes(true)
                           ->findOneBy(['translation' => $translation, 'node' => $gnode]);

            if (null !== $source) {
                $this->get('em')->refresh($source);
                $node = $source->getNode();

                /** @var NodeHandler $nodeHandler */
                $nodeHandler = $this->get('node.handler')->setNode($gnode);

                $this->assignation['translation'] = $translation;
                $this->assignation['available_translations'] = $nodeHandler->getAvailableTranslations();
                $this->assignation['node'] = $node;
                $this->assignation['source'] = $source;

                /*
                 * Form
                 */
                $form = $this->createForm(
                    new NodeSourceType($node->getNodeType()),
                    $source,
                    [
                        'controller' => $this,
                        'entityManager' => $this->get('em'),
                        'container' => $this->getContainer(),
                        'withVirtual' => true,
                        'withTitle' => true,
                    ]
                );
                $form->handleRequest($request);

                if ($form->isSubmitted()) {
                    if ($form->isValid()) {
                        $this->get('em')->flush();
                        /*
                         * Dispatch event
                         */
                        $event = new FilterNodesSourcesEvent($source);
                        $this->get('dispatcher')->dispatch(NodesSourcesEvents::NODE_SOURCE_UPDATED, $event);

                        /*
                         * Update nodeName against source title.
                         */
                        $this->updateNodeName($source);

                        $msg = $this->getTranslator()->trans('node_source.%node_source%.updated.%translation%', [
                            '%node_source%' => $source->getNode()->getNodeName(),
                            '%translation%' => $source->getTranslation()->getName(),
                        ]);

                        $this->publishConfirmMessage($request, $msg, $source);

                        if ($request->isXmlHttpRequest()) {
                            $url = $this->generateUrl($source);
                            $previewUrl = '/preview.php' . str_replace('/dev.php', '', $url);

                            return new JsonResponse([
                                'status' => 'success',
                                'public_url' => $source->getNode()->isPublished() ? $url : $previewUrl,
                                'errors' => []
                            ]);
                        }

                        return $this->redirect($this->generateUrl(
                            'nodesEditSourcePage',
                            ['nodeId' => $node->getId(), 'translationId' => $translation->getId()]
                        ));
                    }

                    /*
                     * Handle errors when Ajax POST requests
                     */
                    if ($request->isXmlHttpRequest()) {
                        $errors = $this->getErrorsAsArray($form);
                        return new JsonResponse([
                            'status' => 'fail',
                            'errors' => $errors,
                            'message' => $this->getTranslator()->trans('form_has_errors.check_you_fields'),
                        ], JsonResponse::HTTP_BAD_REQUEST);
                    }
                }

                $this->assignation['form'] = $form->createView();

                return $this->render('nodes/editSource.html.twig', $this->assignation);
            }
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Return an remove form for requested nodeSource.
     *
     * @param Request $request
     * @param int     $nodeSourceId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function removeAction(Request $request, $nodeSourceId)
    {
        $ns = $this->get("em")->find(NodesSources::class, $nodeSourceId);
        if (null === $ns) {
            throw new ResourceNotFoundException();
        }

        $this->validateNodeAccessForRole('ROLE_ACCESS_NODES_DELETE', $ns->getNode()->getId());

        $builder = $this->createFormBuilder()
                        ->add('nodeId', 'hidden', [
                            'data' => $nodeSourceId,
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ]);

        $form = $builder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Node $node */
            $node = $ns->getNode();
            if ($node->getNodeSources()->count() <= 1) {
                $msg = $this->getTranslator()->trans('node_source.%node_source%.%translation%.cant.deleted', [
                    '%node_source%' => $node->getNodeName(),
                    '%translation%' => $ns->getTranslation()->getName(),
                ]);

                $this->publishErrorMessage($request, $msg);
            } else {
                /*
                 * Dispatch event
                 */
                $event = new FilterNodesSourcesEvent($ns);
                $this->get('dispatcher')->dispatch(NodesSourcesEvents::NODE_SOURCE_DELETED, $event);

                $this->get("em")->remove($ns);
                $this->get("em")->flush();

                $ns = $node->getNodeSources()->first();

                $msg = $this->getTranslator()->trans('node_source.%node_source%.deleted.%translation%', [
                    '%node_source%' => $node->getNodeName(),
                    '%translation%' => $ns->getTranslation()->getName(),
                ]);

                $this->publishConfirmMessage($request, $msg);
            }
            return $this->redirect($this->generateUrl(
                'nodesEditSourcePage',
                ['nodeId' => $node->getId(), "translationId" => $ns->getTranslation()->getId()]
            ));
        }

        $this->assignation["nodeSource"] = $ns;
        $this->assignation['form'] = $form->createView();

        return $this->render('nodes/deleteSource.html.twig', $this->assignation);
    }

    /**
     * Update nodeName when title is available.
     *
     * @param  NodesSources $nodeSource
     */
    protected function updateNodeName(NodesSources $nodeSource)
    {
        $title = $nodeSource->getTitle();

        /*
         * update node name if dynamic option enabled and
         * default translation
         */
        if ("" != $title &&
            true === $nodeSource->getNode()->isDynamicNodeName() &&
            $nodeSource->getTranslation()->isDefaultTranslation()) {
            $testingNodeName = StringHandler::slugify($title);

            /*
             * Node name wont be updated if name already taken OR
             * if it is ALREADY suffixed with a unique ID.
             */
            if ($testingNodeName != $nodeSource->getNode()->getNodeName() &&
                !NodeNameChecker::isNodeNameWithUniqId($testingNodeName, $nodeSource->getNode()->getNodeName())) {
                $alreadyUsed = NodeNameChecker::isNodeNameAlreadyUsed($title, $this->get('em'));
                if (!$alreadyUsed) {
                    $nodeSource->getNode()->setNodeName($title);
                } else {
                    $nodeSource->getNode()->setNodeName($title . '-' . uniqid());
                }
                $this->get('em')->flush();

                /*
                 * Dispatch event
                 */
                $event = new FilterNodeEvent($nodeSource->getNode());
                $this->get('dispatcher')->dispatch(NodeEvents::NODE_UPDATED, $event);
            } else {
                $this->get('logger')->debug('Node name has not be changed.');
            }
        }
    }
}
