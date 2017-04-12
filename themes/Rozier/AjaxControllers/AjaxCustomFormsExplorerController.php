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
 * @file AjaxCustomFormsExplorerController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\AjaxControllers;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\CustomForm;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\Rozier\Models\CustomFormModel;

/**
 * {@inheritdoc}
 */
class AjaxCustomFormsExplorerController extends AbstractAjaxController
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
            'RZ\Roadiz\Core\Entities\CustomForm',
            $arrayFilter
        );
        $listManager->setItemPerPage(40);
        $listManager->handle();

        $customForms = $listManager->getEntities();

        $customFormsArray = $this->normalizeCustomForms($customForms);

        $responseArray = [
            'status' => 'confirm',
            'statusCode' => 200,
            'customForms' => $customFormsArray,
            'customFormsCount' => count($customForms),
            'filters' => $listManager->getAssignation(),
        ];

        return new JsonResponse(
            $responseArray,
            Response::HTTP_OK
        );
    }

    /**
     * Get a CustomForm list from an array of id.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listAction(Request $request)
    {
        if (!$request->query->has('ids') || !is_array($request->query->get('ids'))) {
            throw new InvalidParameterException('Ids should be provided within an array');
        }

        $this->validateAccessForRole('ROLE_ACCESS_NODES');

        $cleanCustomFormsIds = array_filter($request->query->get('ids'));

        /** @var EntityManager $em */
        $em = $this->get('em');
        $customForms = $em->getRepository('RZ\Roadiz\Core\Entities\CustomForm')->findBy([
            'id' => $cleanCustomFormsIds,
        ]);

        // Sort array by ids given in request
        $customForms = $this->sortIsh($customForms, $cleanCustomFormsIds);
        $customFormsArray = $this->normalizeCustomForms($customForms);

        $responseArray = [
            'status' => 'confirm',
            'statusCode' => 200,
            'forms' => $customFormsArray
        ];

        return new JsonResponse(
            $responseArray,
            Response::HTTP_OK
        );
    }

    /**
     * Normalize response CustomForm list result.
     *
     * @param $customForms
     * @return array
     */
    private function normalizeCustomForms($customForms)
    {
        $customFormsArray = [];

        /** @var CustomForm $customForm */
        foreach ($customForms as $customForm) {
            $customFormModel = new CustomFormModel($customForm, $this->getContainer());
            $customFormsArray[] = $customFormModel->toArray();
        }

        return $customFormsArray;
    }
}
