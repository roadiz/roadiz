<?php
declare(strict_types=1);

namespace Themes\Rozier\AjaxControllers;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Config\JoinNodeTypeFieldConfiguration;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use RZ\Roadiz\Core\AbstractEntities\AbstractField;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\String\UnicodeString;
use Symfony\Component\Yaml\Yaml;

/**
 * @package Themes\Rozier\AjaxControllers
 */
class AjaxEntitiesExplorerController extends AbstractAjaxController
{
    /**
     * @param NodeTypeField $nodeTypeField
     * @return array
     */
    protected function getFieldConfiguration(NodeTypeField $nodeTypeField)
    {
        if ($nodeTypeField->getType() !== AbstractField::MANY_TO_MANY_T &&
                $nodeTypeField->getType() !== AbstractField::MANY_TO_ONE_T) {
            throw new InvalidParameterException('nodeTypeField is not a valid entity join.');
        }

        $configs = [
            Yaml::parse($nodeTypeField->getDefaultValues() ?? ''),
        ];
        $processor = new Processor();
        $joinConfig = new JoinNodeTypeFieldConfiguration();

        return $processor->processConfiguration($joinConfig, $configs);
    }

    /**
     * @param Request $request
     *
     * @return Response JSON response
     */
    public function indexAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_BACKEND_USER');

        if (!$request->query->has('nodeTypeFieldId')) {
            throw new InvalidParameterException('nodeTypeFieldId parameter is missing.');
        }

        /** @var NodeTypeField $nodeTypeField */
        $nodeTypeField = $this->get('em')->find(NodeTypeField::class, $request->query->get('nodeTypeFieldId'));
        $configuration = $this->getFieldConfiguration($nodeTypeField);

        $orderBy = [];
        foreach ($configuration['orderBy'] as $order) {
            $orderBy[$order['field']] = $order['direction'];
        }

        $criteria = [];
        foreach ($configuration['where'] as $where) {
            $criteria[$where['field']] = $where['value'];
        }

        /*
         * Manage get request to filter list
         */
        $listManager = $this->createEntityListManager(
            $configuration['classname'],
            $criteria,
            $orderBy
        );
        $listManager->setDisplayingNotPublishedNodes(true);
        $listManager->setItemPerPage(30);
        $listManager->handle();
        $entities = $listManager->getEntities();

        $entitiesArray = $this->normalizeEntities($entities, $configuration);

        $responseArray = [
            'status' => 'confirm',
            'statusCode' => 200,
            'entities' => $entitiesArray,
            'filters' => $listManager->getAssignation(),
        ];

        return new JsonResponse(
            $responseArray
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
        if (!$request->query->has('nodeTypeFieldId')) {
            throw new InvalidParameterException('nodeTypeFieldId parameter is missing.');
        }

        if (!$request->query->has('ids') || !is_array($request->query->get('ids'))) {
            throw new InvalidParameterException('Ids should be provided within an array');
        }

        $this->denyAccessUnlessGranted('ROLE_ACCESS_NODES');

        /** @var EntityManager $em */
        $em = $this->get('em');

        /** @var NodeTypeField $nodeTypeField */
        $nodeTypeField = $this->get('em')->find(NodeTypeField::class, $request->query->get('nodeTypeFieldId'));
        $configuration = $this->getFieldConfiguration($nodeTypeField);
        $cleanNodeIds = array_filter($request->query->get('ids'));

        $entities = $em->getRepository($configuration['classname'])->findBy([
            'id' => $cleanNodeIds,
        ]);

        // Sort array by ids given in request
        $entities = $this->sortIsh($entities, $cleanNodeIds);
        $entitiesArray = $this->normalizeEntities($entities, $configuration);

        $responseArray = [
            'status' => 'confirm',
            'statusCode' => 200,
            'items' => $entitiesArray
        ];

        return new JsonResponse(
            $responseArray
        );
    }

    /**
     * Normalize response Node list result.
     *
     * @param array|\Traversable $entities
     * @param array $configuration
     * @return array
     */
    private function normalizeEntities($entities, array &$configuration)
    {
        $entitiesArray = [];

        /** @var AbstractEntity $entity */
        foreach ($entities as $entity) {
            $alt = $configuration['classname'];
            if (!empty($configuration['alt_displayable'])) {
                $alt = call_user_func([$entity, $configuration['alt_displayable']]);
            }
            $displayable = call_user_func([$entity, $configuration['displayable']]);
            $entitiesArray[] = [
                'id' => $entity->getId(),
                'classname' => (new UnicodeString($alt ?? ''))->truncate(30, '…')->toString(),
                'displayable' => (new UnicodeString($displayable ?? ''))->truncate(30, '…')->toString(),
            ];
        }

        return $entitiesArray;
    }
}
