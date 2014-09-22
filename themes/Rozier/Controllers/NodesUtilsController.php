<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file NodesUtilsController.php
 * @copyright REZO ZERO 2014
 * @author Thomas Aufresne
 */

namespace Themes\Rozier\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\Role;
use RZ\Renzo\Core\Handlers\NodeHandler;
use RZ\Renzo\Core\Serializers\NodeJsonSerializer;
use RZ\Renzo\Core\Serializers\NodeCollectionJsonSerializer;
use RZ\Renzo\Core\Exceptions\EntityAlreadyExistsException;
use Themes\Rozier\RozierApp;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use \Symfony\Component\Form\Form;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * {@inheritdoc}
 */
class NodesUtilsController extends RozierApp
{

    /**
     * Export a Node in a Json file (.rzn).
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $nodeId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function exportAction(Request $request, $nodeId)
    {
        $existingNode = $this->getKernel()->em()
                              ->find('RZ\Renzo\Core\Entities\Node', (int) $nodeId);

        $node = NodeJsonSerializer::serialize($existingNode);

        $response =  new Response(
            $node,
            Response::HTTP_OK,
            array()
        );

        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                'node-' . $existingNode->getNodeName() . '-' . date("YmdHis")  . '.rzn'
            )
        ); // Rezo-Zero Type

        $response->prepare($request);

        return $response;
    }
}
