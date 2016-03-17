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
 * @file AjaxNodesExplorerController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\AjaxControllers;

use RZ\Roadiz\Core\Entities\Node;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * {@inheritdoc}
 */
class AjaxNodesExplorerController extends AbstractAjaxController
{
    /**
     * @param Request $request
     *
     * @return Response JSON response
     */
    public function indexAction(Request $request)
    {
        /*
         * Validate
         */
        if (true !== $notValid = $this->validateRequest($request, 'GET')) {
            return new JsonResponse(
                $notValid,
                Response::HTTP_FORBIDDEN
            );
        }

        $this->validateAccessForRole('ROLE_ACCESS_NODES');

        $arrayFilter = [
            'status' => ['<', Node::DELETED],
        ];

        if (count($request->get('nodeTypes')) > 0) {
            $nodeTypeNames = array_map('trim', $request->get('nodeTypes'));

            $nodeTypes = $this->getService('nodeTypeApi')->getBy([
                'name' => $nodeTypeNames,
            ]);

            if (null !== $nodeTypes && count($nodeTypes) > 0) {
                $arrayFilter['nodeType'] = $nodeTypes;
            }
        }
        /*
         * Manage get request to filter list
         */
        $listManager = $this->createEntityListManager(
            'RZ\Roadiz\Core\Entities\Node',
            $arrayFilter
        );
        $listManager->setItemPerPage(40);
        $listManager->handle();

        $nodes = $listManager->getEntities();
        $nodesArray = [];

        if (null !== $nodes) {
            foreach ($nodes as $node) {
                $nodesArray[] = [
                    'id' => $node->getId(),
                    'filename' => $node->getNodeName(),
                    'html' => $this->getTwig()->render('widgets/nodeSmallThumbnail.html.twig', [
                        'nodeSource' => $node->getNodeSources()->first(),
                        'node' => $node,
                    ]),
                ];
            }
        }

        $responseArray = [
            'status' => 'confirm',
            'statusCode' => 200,
            'nodes' => $nodesArray,
            'nodesCount' => count($nodes),
            'filters' => $listManager->getAssignation(),
        ];

        return new JsonResponse(
            $responseArray,
            Response::HTTP_OK
        );
    }
}
