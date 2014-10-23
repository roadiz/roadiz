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

        $this->validateAccessForRole('ROLE_ACCESS_NODES');

        $node = $this->getService('em')
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
                    'responseText' => $this->getTranslator()->trans('node.%name%.updated', array(
                        '%name%' => $node->getNodeName()
                    ))
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
            'responseText' => $this->getTranslator()->trans('node.%nodeId%.not_exists', array(
                '%nodeId%' => $nodeId
            ))
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

            $parent = $this->getService('em')
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
            $nextNode = $this->getService('em')
                ->find('RZ\Renzo\Core\Entities\Node', (int) $parameters['nextNodeId']);
            if ($nextNode !== null) {
                $node->setPosition($nextNode->getPosition() - 0.5);
            }
        } elseif (!empty($parameters['prevNodeId']) &&
            $parameters['prevNodeId'] > 0) {
            $prevNode = $this->getService('em')
                ->find('RZ\Renzo\Core\Entities\Node', (int) $parameters['prevNodeId']);
            if ($prevNode !== null) {
                $node->setPosition($prevNode->getPosition() + 0.5);
            }
        }
        // Apply position update before cleaning
        $this->getService('em')->flush();

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

        $this->validateAccessForRole('ROLE_ACCESS_NODES');

        $responseArray = null;

        $availableStatuses = array(
            'visible' => 'setVisible',
            'status' => 'setStatus',
            'locked' => 'setLocked',
            'hideChildren' => 'setHidingChildren',
            'sterile' => 'setSterile'
        );

        if ("nodeChangeStatus" == $request->get('_action') &&
            "" != $request->get('statusName')) {

            // just verify role when updating status
            if ($request->get('statusName') == 'status' &&
                $request->get('statusValue') > Node::PENDING &&
                !$this->getService('securityContext')->isGranted('ROLE_ACCESS_NODES_STATUS')) {

                $responseArray = array(
                    'statusCode' => Response::HTTP_FORBIDDEN,
                    'status'    => 'danger',
                    'responseText' => $this->getTranslator()->trans('role.cannot.update.status')
                );

            } else {

                if ($request->get('nodeId') > 0) {

                    $node = $this->getService('em')
                                 ->find('RZ\Renzo\Core\Entities\Node', (int) $request->get('nodeId'));

                    if (null !== $node) {

                        /*
                         * Check if status name is a valid boolean node field.
                         */
                        if (in_array($request->get('statusName'), array_keys($availableStatuses))) {

                            $setter = $availableStatuses[$request->get('statusName')];
                            $value = $request->get('statusValue');

                            if ($this->getSecurityContext()->isGranted('ROLE_ACCESS_NODES_STATUS') ||
                                $request->get('statusName') != 'status') {

                                $node->$setter($value);
                                $this->em()->flush();

                                $responseArray = array(
                                    'statusCode' => Response::HTTP_OK,
                                    'status'    => 'success',
                                    'responseText' => $this->getTranslator()->trans('node.%name%.%field%.updated', array(
                                        '%name%' => $node->getNodeName(),
                                        '%field%' => $request->get('statusName')
                                    )),
                                    'name' => $request->get('statusName'),
                                    'value' => $value
                                );
                            } else {
                                $responseArray = array(
                                    'statusCode' => Response::HTTP_FORBIDDEN,
                                    'status'    => 'danger',
                                    'responseText' => $this->getTranslator()->trans('role.cannot.update.status')
                                );
                            }


                        } else {
                            $responseArray = array(
                                'statusCode' => Response::HTTP_FORBIDDEN,
                                'status'    => 'danger',
                                'responseText' => $this->getTranslator()->trans('node.has_no.field.%field%', array(
                                    '%field%' => $request->get('statusName')
                                ))
                            );
                        }

                    } else {
                        $responseArray = array(
                            'statusCode' => Response::HTTP_FORBIDDEN,
                            'status'    => 'danger',
                            'responseText' => $this->getTranslator()->trans('node.%nodeId%.not_exists', array(
                                '%nodeId%' => $request->get('nodeId')
                            ))
                        );
                    }

                } else {
                    $responseArray = array(
                        'statusCode' => Response::HTTP_FORBIDDEN,
                        'status'    => 'danger',
                        'responseText' => $this->getTranslator()->trans('node.id.not_specified')
                    );
                }
            }


        } else {
            $responseArray = array(
                'statusCode' => Response::HTTP_FORBIDDEN,
                'status'    => 'danger',
                'responseText' => $this->getTranslator()->trans('node.%nodeId%.not_exists', array(
                    '%nodeId%' => $request->get('nodeId')
                ))
            );
        }

        return new Response(
            json_encode($responseArray),
            $responseArray['statusCode'],
            array('content-type' => 'application/javascript')
        );
    }

    public function quickAddAction(Request $request)
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

        $this->validateAccessForRole('ROLE_ACCESS_NODES');

        $responseArray = array();

        if ($request->get('nodeTypeId') > 0 &&
            $request->get('parentNodeId') > 0) {

            $nodeType = $this->getService('em')
                            ->find(
                                'RZ\Renzo\Core\Entities\NodeType',
                                (int) $request->get('nodeTypeId')
                            );

            $parent = $this->getService('em')
                            ->find(
                                'RZ\Renzo\Core\Entities\Node',
                                (int) $request->get('parentNodeId')
                            );

            if (null !== $nodeType &&
                null !== $parent) {

                $translation = $parent->getNodeSources()->first()->getTranslation();

                if (null === $translation) {
                    $translation = $this->getService('em')
                                        ->getRepository('RZ\Renzo\Core\Entities\Translation')
                                        ->findDefault();
                }

                try {
                    $name = "Untitled ".uniqid();

                    $node = new Node($nodeType);
                    $node->setParent($parent);
                    $node->setNodeName($name);
                    $this->getService('em')->persist($node);

                    $sourceClass = "GeneratedNodeSources\\".$nodeType->getSourceEntityClassName();
                    $source = new $sourceClass($node, $translation);
                    $source->setTitle($name);
                    $this->getService('em')->persist($source);
                    $this->getService('em')->flush();

                    $responseArray = array(
                        'statusCode' => Response::HTTP_OK,
                        'status'    => 'success',
                        'responseText' => $this->getTranslator()->trans(
                            'added.node.%name%',
                            array(
                                '%name%' => $source->getTitle()
                            )
                        )
                    );

                } catch (\Exception $e) {
                    $msg = $this->getTranslator()->trans('node.%name%.noCreation.alreadyExists', array('%name%'=>$node->getNodeName()));

                    $responseArray = array(
                        'statusCode' => Response::HTTP_FORBIDDEN,
                        'status'    => 'danger',
                        'responseText' => $msg
                    );
                }


            } else {
                $responseArray = array(
                    'statusCode' => Response::HTTP_FORBIDDEN,
                    'status'    => 'danger',
                    'responseText' => $this->getTranslator()->trans('bad.request')
                );
            }

        } else {
            $responseArray = array(
                'statusCode' => Response::HTTP_FORBIDDEN,
                'status'    => 'danger',
                'responseText' => $this->getTranslator()->trans('bad.request')
            );
        }

        return new Response(
            json_encode($responseArray),
            $responseArray['statusCode'],
            array('content-type' => 'application/javascript')
        );
    }
}
