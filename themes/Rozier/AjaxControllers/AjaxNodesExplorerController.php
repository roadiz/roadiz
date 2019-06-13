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
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Tag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Themes\Rozier\Models\NodeModel;
use Themes\Rozier\Models\NodeSourceModel;

/**
 * {@inheritdoc}
 */
class AjaxNodesExplorerController extends AbstractAjaxController
{
    protected function getItemPerPage()
    {
        return 30;
    }

    /**
     * @param Request $request
     *
     * @return Response JSON response
     */
    public function indexAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_NODES');

        $arrayFilter = $this->parseFilterFromRequest($request);

        if ($request->get('search') !== '' && null !== $this->get('solr.search.nodeSource')) {
            $responseArray = $this->getSolrSearchResults($request, $arrayFilter);
        } else {
            $responseArray = $this->getNodeSearchResults($request, $arrayFilter);
        }

        if ($request->query->has('tagId') && $request->get('tagId') > 0) {
            $responseArray['filters'] = array_merge($responseArray['filters'], [
                'tagId' => $request->get('tagId')
            ]);
        }

        return new JsonResponse(
            $responseArray
        );
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    protected function parseFilterFromRequest(Request $request): array
    {
        $arrayFilter = [
            'status' => ['<=', Node::ARCHIVED],
        ];

        if ($request->query->has('tagId') && $request->get('tagId') > 0) {
            $tag = $this->get('em')
                ->find(
                    Tag::class,
                    $request->get('tagId')
                );

            $arrayFilter['tags'] = [$tag];
        }

        if ($request->query->has('nodeTypes') && count($request->get('nodeTypes')) > 0) {
            $nodeTypeNames = array_map('trim', $request->get('nodeTypes'));

            $nodeTypes = $this->get('nodeTypeApi')->getBy([
                'name' => $nodeTypeNames,
            ]);

            if (null !== $nodeTypes && count($nodeTypes) > 0) {
                $arrayFilter['nodeType'] = $nodeTypes;
            }
        }

        return $arrayFilter;
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    protected function getNodeSearchResults(Request $request, array $arrayFilter): array
    {
        /*
         * Manage get request to filter list
         */
        $listManager = $this->createEntityListManager(
            Node::class,
            $arrayFilter
        );
        $listManager->setDisplayingNotPublishedNodes(true);
        $listManager->setItemPerPage($this->getItemPerPage());
        $listManager->handle();

        $nodes = $listManager->getEntities();
        $nodesArray = $this->normalizeNodes($nodes);
        return [
            'status' => 'confirm',
            'statusCode' => 200,
            'nodes' => $nodesArray,
            'nodesCount' => $listManager->getItemCount(),
            'filters' => $listManager->getAssignation(),
        ];
    }

    /**
     * @param Request $request
     * @param array   $arrayFilter
     *
     * @return array
     */
    protected function getSolrSearchResults(Request $request, array $arrayFilter): array
    {
        $currentPage = $request->get('page', 1);
        $arrayFilter['translation'] = $this->get('defaultTranslation');
        $results = $this->get('solr.search.nodeSource')
            ->searchWithHighlight(
                $request->get('search'),
                $arrayFilter,
                $this->getItemPerPage(),
                true,
                10000,
                $currentPage
            )
        ;
        $resultsCount = $this->get('solr.search.nodeSource')
            ->count(
                $request->get('search'),
                $arrayFilter,
                0,
                true
            );
        $pageCount = ceil($resultsCount/$this->getItemPerPage());
        $nodeSources = array_map(function ($result) {
            return $result['nodeSource'];
        }, $results);
        $nodesArray = $this->normalizeNodes($nodeSources);
        return [
            'status' => 'confirm',
            'statusCode' => 200,
            'nodes' => $nodesArray,
            'nodesCount' => $resultsCount,
            'filters' => [
                'currentPage' => $currentPage,
                'itemCount' => $resultsCount,
                'itemPerPage' => $this->getItemPerPage(),
                'pageCount' => $pageCount,
                'nextPage' => $currentPage < $pageCount ? $currentPage + 1 : null,
            ],
        ];
    }

    /**
     * Get a Node list from an array of id.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_NODES');

        if (!$request->query->has('ids') || !is_array($request->query->get('ids'))) {
            throw new InvalidParameterException('Ids should be provided within an array');
        }

        $cleanNodeIds = array_filter($request->query->get('ids'));

        /** @var EntityManager $em */
        $em = $this->get('em');
        $nodes = $em->getRepository(Node::class)
            ->setDisplayingNotPublishedNodes(true)
            ->findBy([
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
            $responseArray
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

        /** @var Node|NodesSources $doc */
        foreach ($nodes as $node) {
            if (null !== $node) {
                if ($node instanceof NodesSources) {
                    $nodeModel = new NodeSourceModel($node, $this->getContainer());
                } else {
                    $nodeModel = new NodeModel($node, $this->getContainer());
                }
                $nodesArray[] = $nodeModel->toArray();
            }
        }

        return $nodesArray;
    }
}
