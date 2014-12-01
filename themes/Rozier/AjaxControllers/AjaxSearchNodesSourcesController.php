<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file AjaxSearchNodesSourcesController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\AjaxControllers;

use Themes\Rozier\AjaxControllers\AbstractAjaxController;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * {@inheritdoc}
 */
class AjaxSearchNodesSourcesController extends AbstractAjaxController
{
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
                array('content-type' => 'application/javascript')
            );
        }

        $this->validateAccessForRole('ROLE_ACCESS_NODES');

        if ("" != $request->get('searchTerms')) {

            $nodesSources = $this->getService('em')
                ->getRepository('RZ\Roadiz\Core\Entities\NodesSources')
                ->findBySearchQuery(strip_tags($request->get('searchTerms')));

            if (null === $nodesSources) {
                $nodesSources = $this->getService('em')
                    ->getRepository('RZ\Roadiz\Core\Entities\NodesSources')
                    ->searchBy(strip_tags($request->get('searchTerms')));
            }

            if (null !== $nodesSources &&
                count($nodesSources) > 0) {

                $responseArray = array(
                    'statusCode' => '200',
                    'status' => 'success',
                    'data' => array(),
                    'responseText' => count($nodesSources).' results found.'
                );

                foreach ($nodesSources as $source) {
                    $responseArray['data'][] = array(
                        'title' => "" != $source->getTitle() ? $source->getTitle() : $source->getNode()->getNodeName(),
                        'nodeId' => $source->getNode()->getId(),
                        'translationId' => $source->getTranslation()->getId(),
                        'typeName' => $source->getNode()->getNodeType()->getDisplayName(),
                        'typeColor' => $source->getNode()->getNodeType()->getColor(),
                        'url' => $this->getService('urlGenerator')->generate(
                            'nodesEditSourcePage',
                            array(
                                'nodeId' => $source->getNode()->getId(),
                                'translationId' => $source->getTranslation()->getId()
                            )
                        )
                    );
                }

                return new Response(
                    json_encode($responseArray),
                    Response::HTTP_OK,
                    array('content-type' => 'application/javascript')
                );
            }
        }


        $responseArray = array(
            'statusCode' => '403',
            'status'    => 'danger',
            'responseText' => 'No results found.'
        );

        return new Response(
            json_encode($responseArray),
            Response::HTTP_OK,
            array('content-type' => 'application/javascript')
        );
    }
}
