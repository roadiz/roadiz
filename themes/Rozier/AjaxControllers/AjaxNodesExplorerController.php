<?php
declare(strict_types=1);

namespace Themes\Rozier\AjaxControllers;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\SearchEngine\NodeSourceSearchHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Themes\Rozier\Models\NodeModel;
use Themes\Rozier\Models\NodeSourceModel;

/**
 * Class AjaxNodesExplorerController
 *
 * @package Themes\Rozier\AjaxControllers
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
        /** @var NodeSourceSearchHandler|null $searchHandler */
        $searchHandler = $this->get('solr.search.nodeSource');
        if ($request->get('search') !== '' && null !== $searchHandler) {
            $responseArray = $this->getSolrSearchResults($request, $searchHandler, $arrayFilter);
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
     * @param Request                 $request
     * @param NodeSourceSearchHandler $searchHandler
     * @param array                   $arrayFilter
     *
     * @return array
     */
    protected function getSolrSearchResults(Request $request, NodeSourceSearchHandler $searchHandler, array $arrayFilter): array
    {
        $searchHandler->boostByUpdateDate();
        $currentPage = $request->get('page', 1);

        $results = $searchHandler->search(
            $request->get('search'),
            $arrayFilter,
            $this->getItemPerPage(),
            true,
            10000000,
            $currentPage
        );
        $pageCount = ceil($results->getResultCount()/$this->getItemPerPage());
        $nodesArray = $this->normalizeNodes($results);

        return [
            'status' => 'confirm',
            'statusCode' => 200,
            'nodes' => $nodesArray,
            'nodesCount' => $results->getResultCount(),
            'filters' => [
                'currentPage' => $currentPage,
                'itemCount' => $results->getResultCount(),
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
     * @param array|\Traversable $nodes
     * @return array
     */
    private function normalizeNodes($nodes)
    {
        $nodesArray = [];

        /** @var Node|NodesSources $doc */
        foreach ($nodes as $node) {
            if (null !== $node) {
                if ($node instanceof NodesSources) {
                    if (!key_exists($node->getNode()->getId(), $nodesArray)) {
                        $nodeModel = new NodeSourceModel($node, $this->getContainer());
                        $nodesArray[$node->getNode()->getId()] = $nodeModel->toArray();
                    }
                } else {
                    if (!key_exists($node->getId(), $nodesArray)) {
                        $nodeModel = new NodeModel($node, $this->getContainer());
                        $nodesArray[$node->getId()] = $nodeModel->toArray();
                    }
                }
            }
        }

        return array_values($nodesArray);
    }
}
