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
 * @file AjaxExplorerProviderController.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace Themes\Rozier\AjaxControllers;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use themes\Rozier\Explorer\ExplorerItemInterface;
use themes\Rozier\Explorer\ExplorerProviderInterface;

/**
 * {@inheritdoc}
 */
class AjaxExplorerProviderController extends AbstractAjaxController
{
    /**
     * @param Request $request
     * @return Response JSON response
     */
    public function indexAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_BACKEND_USER');

        if (!$request->query->has('providerClass')) {
            throw new InvalidParameterException('providerClass parameter is missing.');
        }

        $providerClass = $request->query->get('providerClass');
        if (!class_exists($providerClass)) {
            throw new InvalidParameterException('providerClass is not a valid class.');
        }

        $provider = new $providerClass();
        if ($provider instanceof ExplorerProviderInterface) {
            $provider->setContainer($this->getContainer());
            $options = [
                'page' => $request->query->get('page') ?: 1,
                'itemPerPage' => $request->query->get('itemPerPage') ?: 30,
                'search' => $request->query->get('search') ?: null,
            ];
            $entities = $provider->getItems($options);

            $entitiesArray = [];
            foreach ($entities as $entity) {
                if ($entity instanceof ExplorerItemInterface) {
                    $entitiesArray[] = $entity->toArray();
                }
            }

            $responseArray = [
                'status' => 'confirm',
                'statusCode' => 200,
                'entities' => $entitiesArray,
                'filters' => $provider->getFilters($options),
            ];

            return new JsonResponse(
                $responseArray,
                Response::HTTP_PARTIAL_CONTENT
            );
        } else {
            throw new InvalidParameterException('providerClass does not implement ExplorerProviderInterface.');
        }
    }

    /**
     * Get a Node list from an array of id.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listAction(Request $request)
    {
        if (!$request->query->has('providerClass')) {
            throw new InvalidParameterException('providerClass parameter is missing.');
        }

        $providerClass = $request->query->get('providerClass');
        if (!class_exists($providerClass)) {
            throw new InvalidParameterException('providerClass is not a valid class.');
        }

        if (!$request->query->has('ids') || !is_array($request->query->get('ids'))) {
            throw new InvalidParameterException('Ids should be provided within an array');
        }

        $this->validateAccessForRole('ROLE_BACKEND_USER');

        $provider = new $providerClass();
        if ($provider instanceof ExplorerProviderInterface) {
            $provider->setContainer($this->getContainer());
            $entitiesArray = [];
            $cleanNodeIds = array_filter($request->query->get('ids'));
            $cleanNodeIds = array_filter($cleanNodeIds, function ($value) {
                $nullValues = ['null', null, 0, '0', false, 'false'];
                return !in_array($value, $nullValues, true);
            });

            if (count($cleanNodeIds) > 0) {
                $entities = $provider->getItemsById($cleanNodeIds);

                foreach ($entities as $entity) {
                    if ($entity instanceof ExplorerItemInterface) {
                        $entitiesArray[] = $entity->toArray();
                    }
                }
            }

            $responseArray = [
                'status' => 'confirm',
                'statusCode' => 200,
                'items' => $entitiesArray
            ];

            return new JsonResponse(
                $responseArray
            );
        } else {
            throw new InvalidParameterException('providerClass does not implement ExplorerProviderInterface.');
        }
    }
}
