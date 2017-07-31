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
 * @file AjaxNodeTypeFieldsController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\AjaxControllers;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Themes\Rozier\Forms\NodeType;
use Themes\Rozier\Models\NodeTypeModel;

/**
 * {@inheritdoc}
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
        $this->validateAccessForRole('ROLE_ACCESS_NODES');
        $arrayFilter = [];

        /*
         * Manage get request to filter list
         */
        $listManager = $this->createEntityListManager(
            'RZ\Roadiz\Core\Entities\NodeType',
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
            $responseArray,
            Response::HTTP_OK
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
        $this->validateAccessForRole('ROLE_ACCESS_NODES');

        if (!$request->query->has('names') || !is_array($request->query->get('names'))) {
            throw new InvalidParameterException('Names array should be provided within an array');
        }

        $cleanNodeTypesName = array_filter($request->query->get('names'));

        /** @var EntityManager $em */
        $em = $this->get('em');
        $nodeTypes = $em->getRepository('RZ\Roadiz\Core\Entities\NodeType')->findBy([
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
            $responseArray,
            Response::HTTP_OK
        );
    }

    /**
     * Normalize response NodeType list result.
     *
     * @param $nodeTypes
     * @return array
     */
    private function normalizeNodeType($nodeTypes)
    {
        $nodeTypesArray = [];

        /** @var NodeType $doc */
        foreach ($nodeTypes as $nodeType) {
            $nodeModel = new NodeTypeModel($nodeType, $this->getContainer());
            $nodeTypesArray[] = $nodeModel->toArray();
        }

        return $nodeTypesArray;
    }
}
