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
 * @file AjaxSearchNodesSourcesController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\AjaxControllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\Rozier\AjaxControllers\AbstractAjaxController;

/**
 * {@inheritdoc}
 */
class AjaxSearchNodesSourcesController extends AbstractAjaxController
{
    const RESULT_COUNT = 8;

    /**
     * Handle AJAX edition requests for Node
     * such as comming from nodetree widgets.
     *
     * @param Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response JSON response
     */
    public function searchAction(Request $request)
    {
        /*
         * Validate
         */
        if (true !== $notValid = $this->validateRequest($request)) {
            return new Response(
                json_encode($notValid),
                Response::HTTP_OK,
                ['content-type' => 'application/javascript']
            );
        }

        $this->validateAccessForRole('ROLE_ACCESS_NODES');

        if ("" != $request->get('searchTerms')) {
            $nodesSources = $this->getService('em')
                                 ->getRepository('RZ\Roadiz\Core\Entities\NodesSources')
                                 ->findBySearchQuery(
                                     strip_tags($request->get('searchTerms')),
                                     static::RESULT_COUNT
                                 );

            if (null === $nodesSources) {
                $nodesSources = $this->getService('em')
                                     ->getRepository('RZ\Roadiz\Core\Entities\NodesSources')
                                     ->searchBy(
                                         strip_tags($request->get('searchTerms')),
                                         [],
                                         [],
                                         static::RESULT_COUNT
                                     );
            }

            if (null !== $nodesSources &&
                count($nodesSources) > 0) {
                $responseArray = [
                    'statusCode' => '200',
                    'status' => 'success',
                    'data' => [],
                    'responseText' => count($nodesSources) . ' results found.',
                ];

                foreach ($nodesSources as $source) {
                    $responseArray['data'][] = [
                        'title' => "" != $source->getTitle() ? $source->getTitle() : $source->getNode()->getNodeName(),
                        'nodeId' => $source->getNode()->getId(),
                        'translationId' => $source->getTranslation()->getId(),
                        'typeName' => $source->getNode()->getNodeType()->getDisplayName(),
                        'typeColor' => $source->getNode()->getNodeType()->getColor(),
                        'url' => $this->getService('urlGenerator')->generate(
                            'nodesEditSourcePage',
                            [
                                'nodeId' => $source->getNode()->getId(),
                                'translationId' => $source->getTranslation()->getId(),
                            ]
                        ),
                    ];
                }

                return new Response(
                    json_encode($responseArray),
                    Response::HTTP_OK,
                    ['content-type' => 'application/javascript']
                );
            }
        }

        $responseArray = [
            'statusCode' => '403',
            'status' => 'danger',
            'responseText' => 'No results found.',
        ];

        return new Response(
            json_encode($responseArray),
            Response::HTTP_OK,
            ['content-type' => 'application/javascript']
        );
    }
}
