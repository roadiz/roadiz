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

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Handlers\NodeHandler;
use Themes\Rozier\AjaxControllers\AbstractAjaxController;
use Themes\Rozier\RozierApp;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

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
        if (!($this->getSecurityContext()->isGranted('ROLE_ACCESS_NODES')
            || $this->getSecurityContext()->isGranted('ROLE_SUPERADMIN')))
            return $this->throw404();

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

        if ("" != $request->get('searchTerms')) {

            $nodesSources = $this->getService('em')
                ->getRepository('RZ\Renzo\Core\Entities\NodesSources')
                ->findBySearchQuery(strip_tags($request->get('searchTerms')));

            if (null === $nodesSources) {
                $nodesSources = $this->getService('em')
                    ->getRepository('RZ\Renzo\Core\Entities\NodesSources')
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
