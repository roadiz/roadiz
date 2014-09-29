<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file AjaxNodesController.php
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
class AjaxNodesController extends AbstractAjaxController
{
    /**
     * Handle AJAX edition requests for Node
     * such as comming from nodetree widgets.
     *
     * @param Request $request
     * @param int     $nodeId
     *
     * @return Symfony\Component\HttpFoundation\Response JSON response
     */
    public function editAction(Request $request, $nodeId)
    {
        /*
         * Validate
         */
        if (true !== $notValid = $this->validateRequest($request)) {
            return new Response(
                json_encode($notValid),
                Response::HTTP_FORBIDDEN,
                array('content-type' => 'application/javascript')
            );
        }

        $node = $this->getKernel()->em()
            ->find('RZ\Renzo\Core\Entities\Node', (int) $nodeId);

        if ($node !== null) {

            $responseArray = null;

            /*
             * Get the right update method against "_action" parameter
             */
            switch ($request->get('_action')) {
                case 'updatePosition':
                    $responseArray = $this->updatePosition($request->request->all(), $node);
                    break;
            }

            if ($responseArray === null) {
                $responseArray = array(
                    'statusCode' => '200',
                    'status' => 'success',
                    'responseText' => ('Node '.$nodeId.' edited ')
                );
            }

            return new Response(
                json_encode($responseArray),
                Response::HTTP_OK,
                array('content-type' => 'application/javascript')
            );
        }


        $responseArray = array(
            'statusCode' => '403',
            'status'    => 'danger',
            'responseText' => 'Node '.$nodeId.' does not exists'
        );

        return new Response(
            json_encode($responseArray),
            Response::HTTP_OK,
            array('content-type' => 'application/javascript')
        );
    }

    /**
     * @param array $parameters
     * @param Node  $node
     */
    protected function updatePosition($parameters, Node $node)
    {
        /*
         * First, we set the new parent
         */
        $parent = null;

        if (!empty($parameters['newParent']) &&
            $parameters['newParent'] > 0) {

            $parent = $this->getKernel()->em()
                ->find('RZ\Renzo\Core\Entities\Node', (int) $parameters['newParent']);

            if ($parent !== null) {
                $node->setParent($parent);
            }
        } elseif ($parameters['newParent'] == null) {
            $node->setParent(null);
        }

        /*
         * Then compute new position
         */
        if (!empty($parameters['nextNodeId']) &&
            $parameters['nextNodeId'] > 0) {
            $nextNode = $this->getKernel()->em()
                ->find('RZ\Renzo\Core\Entities\Node', (int) $parameters['nextNodeId']);
            if ($nextNode !== null) {
                $node->setPosition($nextNode->getPosition() - 0.5);
            }
        } elseif (!empty($parameters['prevNodeId']) &&
            $parameters['prevNodeId'] > 0) {
            $prevNode = $this->getKernel()->em()
                ->find('RZ\Renzo\Core\Entities\Node', (int) $parameters['prevNodeId']);
            if ($prevNode !== null) {
                $node->setPosition($prevNode->getPosition() + 0.5);
            }
        }
        // Apply position update before cleaning
        $this->getKernel()->em()->flush();

        if ($parent !== null) {
            $parent->getHandler()->cleanChildrenPositions();
        } else {
            NodeHandler::cleanRootNodesPositions();
        }
    }

    /**
     * Update node's status.
     *
     * @param Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function statusesAction(Request $request)
    {
        /*
         * Validate
         */
        if (true !== $notValid = $this->validateRequest($request)) {
            return new Response(
                json_encode($notValid),
                Response::HTTP_FORBIDDEN,
                array('content-type' => 'application/javascript')
            );
        }

        $responseArray = null;

        $availableStatuses = array(
            'visible' => 'setVisible',
            'published' => 'setPublished',
            'locked' => 'setLocked',
            'hideChildren' => 'setHidingChildren',
            'archived' => 'setArchived',
            'sterile' => 'setSterile'
        );

        if ("nodeChangeStatus" == $request->get('_action') &&
            "" != $request->get('statusName')) {

            if ($request->get('nodeId') > 0) {

                $node = $this->getKernel()->em()
                             ->find('RZ\Renzo\Core\Entities\Node', (int) $request->get('nodeId'));

                if (null !== $node) {

                    /*
                     * Check if status name is a valid boolean node field.
                     */
                    if (in_array($request->get('statusName'), array_keys($availableStatuses))) {

                        $setter = $availableStatuses[$request->get('statusName')];
                        $value = filter_var($request->get('statusValue'), FILTER_VALIDATE_BOOLEAN);
                        if ($request->get('statusName') == 'published') {
                            if (!($this->getSecurityContext()->isGranted('ROLE_ACCESS_NODES_PUBLISH')
                                || $this->getSecurityContext()->isGranted('ROLE_SUPERADMIN')))
                                $node->$setter(false);
                            $node->$setter($value);
                        } else {
                            $node->$setter($value);
                        }
                        $this->em()->flush();

                        $responseArray = array(
                            'statusCode' => Response::HTTP_OK,
                            'status'    => 'success',
                            'responseText' => 'Node '.$request->get('nodeId').'’s '.
                                              $request->get('statusName').' status has been updated.'.
                                              '['.$setter.'('.$value.' '.gettype($value).')]'
                        );

                    } else {
                        $responseArray = array(
                            'statusCode' => Response::HTTP_FORBIDDEN,
                            'status'    => 'danger',
                            'responseText' => 'Node has no field '.$request->get('statusName').'.'
                        );
                    }

                } else {
                    $responseArray = array(
                        'statusCode' => Response::HTTP_FORBIDDEN,
                        'status'    => 'danger',
                        'responseText' => 'Node '.$request->get('nodeId').' does not exists'
                    );
                }

            } else {
                $responseArray = array(
                    'statusCode' => Response::HTTP_FORBIDDEN,
                    'status'    => 'danger',
                    'responseText' => 'No node-id specified.'
                );
            }

        } else {
            $responseArray = array(
                'statusCode' => Response::HTTP_FORBIDDEN,
                'status'    => 'danger',
                'responseText' => 'Node '.$request->get('nodeId').' does not exists'
            );
        }

        return new Response(
            json_encode($responseArray),
            $responseArray['statusCode'],
            array('content-type' => 'application/javascript')
        );
    }
}
