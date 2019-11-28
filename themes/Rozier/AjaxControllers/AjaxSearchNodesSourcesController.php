<?php
/**
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
 * @file AjaxSearchNodesSourcesController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\AjaxControllers;

use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\SearchEngine\GlobalNodeSourceSearchHandler;
use RZ\Roadiz\Core\SearchEngine\NodeSourceSearchHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class AjaxSearchNodesSourcesController
 * @package Themes\Rozier\AjaxControllers
 */
class AjaxSearchNodesSourcesController extends AbstractAjaxController
{
    const RESULT_COUNT = 8;

    /**
     * Handle AJAX edition requests for Node
     * such as coming from nodetree widgets.
     *
     * @param Request $request
     *
     * @return Response JSON response
     */
    public function searchAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODES');

        if (!$request->query->has('searchTerms') || $request->query->get('searchTerms') == '') {
            throw new BadRequestHttpException('searchTerms parameter is missing.');
        }

        /** @var NodeSourceSearchHandler|null $searchHandler */
        $searchHandler = $this->get('solr.search.nodeSource');
        if (null !== $searchHandler) {
            $searchHandler->boostByUpdateDate();
            $results = $searchHandler->searchWithHighlight(
                $request->get('searchTerms'),
                [],
                static::RESULT_COUNT,
                false,
                10000
            );
            $nodesSources = array_map(function ($result) {
                return $result['nodeSource'];
            }, $results);
        } else {
            $searchHandler = new GlobalNodeSourceSearchHandler($this->get('em'));
            $searchHandler->setDisplayNonPublishedNodes(true);

            /** @var array $nodesSources */
            $nodesSources = $searchHandler->getNodeSourcesBySearchTerm(
                $request->get('searchTerms'),
                static::RESULT_COUNT,
                $this->get('defaultTranslation')
            );
        }


        if (null !== $nodesSources && count($nodesSources) > 0) {
            $responseArray = [
                'statusCode' => Response::HTTP_OK,
                'status' => 'success',
                'data' => [],
                'responseText' => count($nodesSources) . ' results found.',
            ];

            foreach ($nodesSources as $source) {
                if (null !== $source && $source instanceof NodesSources) {
                    $responseArray['data'][] = [
                        'title' => "" != $source->getTitle() ? $source->getTitle() : $source->getNode()->getNodeName(),
                        'nodeId' => $source->getNode()->getId(),
                        'translationId' => $source->getTranslation()->getId(),
                        'typeName' => $source->getNode()->getNodeType()->getDisplayName(),
                        'typeColor' => $source->getNode()->getNodeType()->getColor(),
                        'url' => $this->generateUrl(
                            'nodesEditSourcePage',
                            [
                                'nodeId' => $source->getNode()->getId(),
                                'translationId' => $source->getTranslation()->getId(),
                            ]
                        ),
                    ];
                }
            }

            return new JsonResponse(
                $responseArray
            );
        }

        return new JsonResponse([
            'statusCode' => Response::HTTP_OK,
            'status' => 'success',
            'data' => [],
            'responseText' => 'No results found.',
        ]);
    }
}
