<?php
/**
 * Copyright (c) 2017. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file AjaxEntitiesExplorerController.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace Themes\Rozier\AjaxControllers;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Config\JoinNodeTypeFieldConfiguration;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Yaml\Yaml;

/**
 * {@inheritdoc}
 */
class AjaxEntitiesExplorerController extends AbstractAjaxController
{
    /**
     * @param NodeTypeField $nodeTypeField
     * @return array
     */
    protected function getFieldConfiguration(NodeTypeField $nodeTypeField)
    {
        if (null === $nodeTypeField ||
            ($nodeTypeField->getType() !== NodeTypeField::MANY_TO_MANY_T &&
                $nodeTypeField->getType() !== NodeTypeField::MANY_TO_ONE_T)) {
            throw new InvalidParameterException('nodeTypeField is not a valid entity join.');
        }

        $configs = [
            Yaml::parse($nodeTypeField->getDefaultValues()),
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
            $entitiesArray[] = [
                'id' => $entity->getId(),
                'classname' => $alt,
                'displayable' => call_user_func([$entity, $configuration['displayable']]),
            ];
        }

        return $entitiesArray;
    }
}
