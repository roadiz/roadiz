<?php
declare(strict_types=1);

namespace Themes\Rozier\AjaxControllers;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\NodeType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Themes\Rozier\Models\NodeTypeModel;

/**
 * @package Themes\Rozier\AjaxControllers
 */
class AjaxNodeTypesController extends AjaxAbstractFieldsController
{

    /**
     * @param Request $request
     *
     * @return Response JSON response
     */
    public function indexAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_NODES');
        $arrayFilter = [];

        /*
         * Manage get request to filter list
         */
        $listManager = $this->createEntityListManager(
            NodeType::class,
            $arrayFilter
        );
        $listManager->setDisplayingNotPublishedNodes(true);
        $listManager->setItemPerPage(30);
        $listManager->handle();

        $nodeTypes = $listManager->getEntities();
        $documentsArray = $this->normalizeNodeType($nodeTypes);

        $responseArray = [
            'status' => 'confirm',
            'statusCode' => 200,
            'nodeTypes' => $documentsArray,
            'nodeTypesCount' => count($nodeTypes),
            'filters' => $listManager->getAssignation()
        ];

        return new JsonResponse(
            $responseArray
        );
    }

    /**
     * Get a NodeType list from an array of id.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_NODES');

        if (!$request->query->has('names') || !is_array($request->query->get('names'))) {
            throw new InvalidParameterException('Names array should be provided within an array');
        }

        $cleanNodeTypesName = array_filter($request->query->get('names'));

        /** @var EntityManager $em */
        $em = $this->get('em');
        $nodeTypes = $em->getRepository(NodeType::class)->findBy([
            'name' => $cleanNodeTypesName
        ]);

        // Sort array by ids given in request
        $nodesArray = $this->normalizeNodeType($nodeTypes);

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
     * Normalize response NodeType list result.
     *
     * @param array<NodeType>|\Traversable<NodeType> $nodeTypes
     * @return array
     */
    private function normalizeNodeType($nodeTypes)
    {
        $nodeTypesArray = [];

        /** @var NodeType $nodeType */
        foreach ($nodeTypes as $nodeType) {
            $nodeModel = new NodeTypeModel($nodeType);
            $nodeTypesArray[] = $nodeModel->toArray();
        }

        return $nodeTypesArray;
    }
}
