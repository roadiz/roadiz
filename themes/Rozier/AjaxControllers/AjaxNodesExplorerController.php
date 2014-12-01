<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file AjaxNodesExplorerController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\AjaxControllers;

use Themes\Rozier\AjaxControllers\AbstractAjaxController;
use RZ\Roadiz\Core\ListManagers\EntityListManager;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * {@inheritdoc}
 */
class AjaxNodesExplorerController extends AbstractAjaxController
{
    /**
     * @param Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response JSON response
     */
    public function indexAction(Request $request)
    {
        /*
         * Validate
         */
        if (true !== $notValid = $this->validateRequest($request, 'GET')) {
            return new Response(
                json_encode($notValid),
                Response::HTTP_OK,
                array('content-type' => 'application/javascript')
            );
        }

        $this->validateAccessForRole('ROLE_ACCESS_NODES');

        $arrayFilter = array();
        /*
         * Manage get request to filter list
         */
        $listManager = new EntityListManager(
            $request,
            $this->getService('em'),
            'RZ\Roadiz\Core\Entities\Node',
            $arrayFilter
        );
        $listManager->setItemPerPage(40);
        $listManager->handle();

        $nodes = $listManager->getEntities();

        $nodesArray = array();
        foreach ($nodes as $node) {
            $nodesArray[] = array(
                'id' => $node->getId(),
                'filename'=>$node->getNodeName(),
                'html' => $this->getTwig()->render('widgets/nodeSmallThumbnail.html.twig', array('node'=>$node)),
            );
        }

        $responseArray = array(
            'status' => 'confirm',
            'statusCode' => 200,
            'nodes' => $nodesArray,
            'nodesCount' => count($nodes),
            'filters' => $listManager->getAssignation()
        );

        return new Response(
            json_encode($responseArray),
            Response::HTTP_OK,
            array('content-type' => 'application/javascript')
        );
    }
}
