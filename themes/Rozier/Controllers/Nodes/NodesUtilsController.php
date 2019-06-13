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
 * @file NodesUtilsController.php
 * @author Thomas Aufresne
 */
namespace Themes\Rozier\Controllers\Nodes;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Events\FilterNodeEvent;
use RZ\Roadiz\Core\Events\NodeEvents;
use RZ\Roadiz\Core\Serializers\NodeJsonSerializer;
use RZ\Roadiz\Utils\Node\NodeDuplicator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Themes\Rozier\RozierApp;

/**
 * {@inheritdoc}
 */
class NodesUtilsController extends RozierApp
{

    /**
     * Export a Node in a Json file (.rzn).
     *
     * @param Request $request
     * @param int     $nodeId
     *
     * @return Response
     */
    public function exportAction(Request $request, $nodeId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_NODES');

        /** @var Node $existingNode */
        $existingNode = $this->get('em')
            ->find(Node::class, (int) $nodeId);
        $this->get('em')->refresh($existingNode);

        $serializer = new NodeJsonSerializer($this->get('em'));
        $node = $serializer->serialize([$existingNode]);

        $response = new Response(
            $node,
            Response::HTTP_OK,
            []
        );

        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                'node-' . $existingNode->getNodeName() . '-' . date("YmdHis") . '.rzn'
            )
        ); // Rezo-Zero Type

        $response->prepare($request);

        return $response;
    }

    /**
     * Export all Node in a Json file (.rzn).
     *
     * @param Request $request
     *
     * @return Response
     */
    public function exportAllAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_NODES');

        /** @var Node[] $existingNodes */
        $existingNodes = $this->get('em')
            ->getRepository(Node::class)
            ->setDisplayingNotPublishedNodes(true)
            ->findBy(["parent" => null]);

        foreach ($existingNodes as $existingNode) {
            $this->get('em')->refresh($existingNode);
        }

        $serializer = new NodeJsonSerializer($this->get('em'));
        $node = $serializer->serialize($existingNodes);

        $response = new Response(
            $node,
            Response::HTTP_OK,
            []
        );

        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                'node-all-' . date("YmdHis") . '.rzn'
            )
        );

        $response->prepare($request);

        return $response;
    }

    /**
     * Duplicate node by ID
     *
     * @param Request $request
     * @param int     $nodeId
     *
     * @return Response
     */
    public function duplicateAction(Request $request, $nodeId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_NODES');

        /** @var Node $existingNode */
        $existingNode = $this->get('em')->find(Node::class, (int) $nodeId);

        try {
            $duplicator = new NodeDuplicator($existingNode, $this->get('em'));
            $newNode = $duplicator->duplicate();

            /*
             * Dispatch event
             */
            $event = new FilterNodeEvent($newNode);
            $this->get('dispatcher')->dispatch(NodeEvents::NODE_CREATED, $event);
            $this->get('dispatcher')->dispatch(NodeEvents::NODE_DUPLICATED, $event);

            $msg = $this->getTranslator()->trans("duplicated.node.%name%", [
                '%name%' => $existingNode->getNodeName(),
            ]);

            $this->publishConfirmMessage($request, $msg, $newNode->getNodeSources()->first());

            return $this->redirect($this->get('urlGenerator')
                    ->generate(
                        'nodesEditPage',
                        ["nodeId" => $newNode->getId()]
                    ));
        } catch (\Exception $e) {
            $request->getSession()->getFlashBag()->add(
                'error',
                $this->getTranslator()->trans("impossible.duplicate.node.%name%", [
                    '%name%' => $existingNode->getNodeName(),
                ])
            );
            $request->getSession()->getFlashBag()->add('error', $e->getMessage());

            return $this->redirect($this->get('urlGenerator')
                    ->generate(
                        'nodesEditPage',
                        ["nodeId" => $existingNode->getId()]
                    ));
        }
    }
}
