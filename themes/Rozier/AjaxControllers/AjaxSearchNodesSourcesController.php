<?php
declare(strict_types=1);

namespace Themes\Rozier\AjaxControllers;

use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\SearchEngine\GlobalNodeSourceSearchHandler;
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
        $this->denyAccessUnlessGranted('ROLE_ACCESS_NODES');

        if (!$request->query->has('searchTerms') || $request->query->get('searchTerms') == '') {
            throw new BadRequestHttpException('searchTerms parameter is missing.');
        }

        $searchHandler = new GlobalNodeSourceSearchHandler($this->get('em'));
        $searchHandler->setDisplayNonPublishedNodes(true);

        /** @var array $nodesSources */
        $nodesSources = $searchHandler->getNodeSourcesBySearchTerm(
            $request->get('searchTerms'),
            static::RESULT_COUNT
        );

        if (null !== $nodesSources && count($nodesSources) > 0) {
            $responseArray = [
                'statusCode' => Response::HTTP_OK,
                'status' => 'success',
                'data' => [],
                'responseText' => count($nodesSources) . ' results found.',
            ];

            foreach ($nodesSources as $source) {
                if (!key_exists($source->getNode()->getId(), $responseArray['data']) &&
                    null !== $source &&
                    $source instanceof NodesSources) {
                    $responseArray['data'][$source->getNode()->getId()] = [
                        'title' => $source->getTitle() ?? $source->getNode()->getNodeName(),
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
            /*
             * Only display one nodeSource
             */
            $responseArray['data'] = array_values($responseArray['data']);

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
