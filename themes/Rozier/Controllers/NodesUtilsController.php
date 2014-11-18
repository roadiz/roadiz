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
        $this->validateAccessForRole('ROLE_ACCESS_NODES');

        $existingNode = $this->getService('em')
                              ->find('RZ\Renzo\Core\Entities\Node', (int) $nodeId);
        $this->getService('em')->refresh($existingNode);
        $node = NodeJsonSerializer::serialize(array($existingNode));

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

    /**
     * Export all Node in a Json file (.rzn).
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function exportAllAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODES');

        $existingNodes = $this->getService('em')
                              ->getRepository('RZ\Renzo\Core\Entities\Node')
                              ->findBy(array("parent"=>null));

        foreach ($existingNodes as $existingNode) {
            $this->getService('em')->refresh($existingNode);
        }

        $node = NodeJsonSerializer::serialize($existingNodes);

        $response =  new Response(
            $node,
            Response::HTTP_OK,
            array()
        );

        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                'node-all-' . date("YmdHis")  . '.rzn'
            )
        ); // Rezo-Zero Type

        $response->prepare($request);

        return $response;
    }

    /**
     * Duplicate node by ID
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $nodeId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function duplicateAction(Request $request, $nodeId)
    {
        try {

            $existingNode = $this->getService('em')
                                  ->find('RZ\Renzo\Core\Entities\Node', (int) $nodeId);
            $newNode = $existingNode->getHandler()->duplicate();

            $msg = $this->getTranslator()->trans("duplicated.node.%name%", array(
                '%name%' => $existingNode->getNodeName()
            ));
            $request->getSession()->getFlashBag()->add(
                'confirm',
                $msg
            );
            $this->getService('logger')->info($msg);

            $response = new RedirectResponse($this->getService('urlGenerator')
                                                  ->generate('nodesEditPage',
                                                             array("nodeId" => $newNode->getId())));
        } catch(\Exception $e) {

            $request->getSession()->getFlashBag()->add(
                'error',
                $this->getTranslator()->trans("impossible.duplicate.node.%name%", array(
                    '%name%' => $existingNode->getNodeName()
                ))
            );
            $request->getSession()->getFlashBag()->add(
                'error',
                $e->getMessage()
            );
            $response = new RedirectResponse($this->getService('urlGenerator')
                                                      ->generate('nodesEditPage',
                                                                 array("nodeId" => $existingNode->getId())));
        } finally {
            $response->prepare($request);
            return $response->send();
        }
    }
}
