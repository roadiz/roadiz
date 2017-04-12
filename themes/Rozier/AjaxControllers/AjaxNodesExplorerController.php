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

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\Node;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Themes\Rozier\Models\NodeModel;

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
        $this->validateAccessForRole('ROLE_ACCESS_NODES');

        $arrayFilter = [
            'status' => ['<', Node::DELETED],
        ];

        if ($request->get('tagId') > 0) {
            $tag = $this->get('em')
                ->find(
                    'RZ\Roadiz\Core\Entities\Tag',
                    $request->get('tagId')
                );

            $arrayFilter['tags'] = [$tag];
        }

        if (count($request->get('nodeTypes')) > 0) {
            $nodeTypeNames = array_map('trim', $request->get('nodeTypes'));

            $nodeTypes = $this->get('nodeTypeApi')->getBy([
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
        $listManager->setItemPerPage(30);
        $listManager->handle();

        $nodes = $listManager->getEntities();
        $nodesArray = $this->normalizeNodes($nodes);

        $responseArray = [
            'status' => 'confirm',
            'statusCode' => 200,
            'nodes' => $nodesArray,
            'nodesCount' => $listManager->getItemCount(),
            'filters' => $listManager->getAssignation(),
        ];

        if ($request->get('tagId') > 0) {
            $responseArray['filters'] = array_merge($responseArray['filters'], [
                'tagId' => $request->get('tagId')
            ]);
        }

        return new JsonResponse(
            $responseArray,
            Response::HTTP_OK
        );
    }

    /**
     * Get a Node list from an array of id.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listAction(Request $request)
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

        if (!$request->query->has('ids') || !is_array($request->query->get('ids'))) {
            throw new InvalidParameterException('Ids should be provided within an array');
        }

        $this->validateAccessForRole('ROLE_ACCESS_NODES');

        $cleanNodeIds = array_filter($request->query->get('ids'));

        /** @var EntityManager $em */
        $em = $this->get('em');
        $nodes = $em->getRepository('RZ\Roadiz\Core\Entities\Node')->findBy([
            'id' => $cleanNodeIds,
        ]);

        // Sort array by ids given in request
        $nodes = $this->sortIsh($nodes, $cleanNodeIds);
        $nodesArray = $this->normalizeNodes($nodes);

        $responseArray = [
            'status' => 'confirm',
            'statusCode' => 200,
            'items' => $nodesArray
        ];

        return new JsonResponse(
            $responseArray,
            Response::HTTP_OK
        );
    }

    /**
     * Normalize response Node list result.
     *
     * @param $nodes
     * @return array
     */
    private function normalizeNodes($nodes)
    {
        $nodesArray = [];

        /** @var Node $doc */
        foreach ($nodes as $node) {
            $nodeModel = new NodeModel($node, $this->getContainer());
            $nodesArray[] = $nodeModel->toArray();
        }

        return $nodesArray;
    }
}
