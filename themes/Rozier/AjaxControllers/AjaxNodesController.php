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
 * @file AjaxNodesController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\AjaxControllers;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Events\FilterNodeEvent;
use RZ\Roadiz\Core\Events\NodeEvents;
use RZ\Roadiz\Core\Handlers\NodeHandler;
use RZ\Roadiz\Utils\Node\UniqueNodeGenerator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * {@inheritdoc}
 */
class AjaxNodesController extends AbstractAjaxController
{
    /**
     *
     * @param  Request $request [description]
     * @param  int  $nodeId  [description]
     * @return JsonResponse
     */
    public function getTagsAction(Request $request, $nodeId)
    {
        /*
         * Validate
         */
        if (true !== $notValid = $this->validateRequest($request, 'GET')) {
            return new JsonResponse(
                $notValid,
                Response::HTTP_FORBIDDEN,
                ['content-type' => 'application/javascript']
            );
        }
        $this->validateAccessForRole('ROLE_ACCESS_NODES');
        $tags = [];
        $node = $this->getService('em')
            ->find('RZ\Roadiz\Core\Entities\Node', (int) $nodeId);

        foreach ($node->getTags() as $tag) {
            $tags[] = $tag->getHandler()->getFullPath();
        }

        return new JsonResponse(
            $tags,
            Response::HTTP_OK,
            ['content-type' => 'application/javascript']
        );
    }

    /**
     * Handle AJAX edition requests for Node
     * such as comming from nodetree widgets.
     *
     * @param Request $request
     * @param int     $nodeId
     *
     * @return Response JSON response
     */
    public function editAction(Request $request, $nodeId)
    {
        /*
         * Validate
         */
        if (true !== $notValid = $this->validateRequest($request)) {
            return new JsonResponse(
                $notValid,
                Response::HTTP_FORBIDDEN,
                ['content-type' => 'application/javascript']
            );
        }

        $this->validateAccessForRole('ROLE_ACCESS_NODES');

        $node = $this->getService('em')
            ->find('RZ\Roadiz\Core\Entities\Node', (int) $nodeId);

        if ($node !== null) {
            $responseArray = null;

            /*
             * Get the right update method against "_action" parameter
             */
            switch ($request->get('_action')) {
                case 'updatePosition':
                    $responseArray = $this->updatePosition($request->request->all(), $node);
                    break;
                case 'duplicate':
                    $newNode = $node->getHandler()->duplicate();
                    /*
                     * Dispatch event
                     */
                    $event = new FilterNodeEvent($newNode);
                    $this->getService('dispatcher')->dispatch(NodeEvents::NODE_CREATED, $event);

                    $responseArray = [
                        'statusCode' => '200',
                        'status' => 'success',
                        'responseText' => $this->getTranslator()->trans('duplicated.node.%name%', [
                            '%name%' => $node->getNodeName(),
                        ]),
                    ];
                    break;
            }

            if ($responseArray === null) {
                $responseArray = [
                    'statusCode' => '200',
                    'status' => 'success',
                    'responseText' => $this->getTranslator()->trans('node.%name%.updated', [
                        '%name%' => $node->getNodeName(),
                    ]),
                ];
            }

            return new JsonResponse(
                $responseArray,
                Response::HTTP_OK,
                ['content-type' => 'application/javascript']
            );
        }

        $responseArray = [
            'statusCode' => '403',
            'status' => 'danger',
            'responseText' => $this->getTranslator()->trans('node.%nodeId%.not_exists', [
                '%nodeId%' => $nodeId,
            ]),
        ];

        return new JsonResponse(
            $responseArray,
            Response::HTTP_OK,
            ['content-type' => 'application/javascript']
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
                ->find('RZ\Roadiz\Core\Entities\Node', (int) $parameters['newParent']);

            if ($parent !== null) {
                $node->setParent($parent);
            }
        } else {
            // if no parent or null we place node at root level
            $node->setParent(null);
        }

        /*
         * Then compute new position
         */
        if (!empty($parameters['nextNodeId']) &&
            $parameters['nextNodeId'] > 0) {
            $nextNode = $this->getService('em')
                ->find('RZ\Roadiz\Core\Entities\Node', (int) $parameters['nextNodeId']);
            if ($nextNode !== null) {
                $node->setPosition($nextNode->getPosition() - 0.5);
            }
        } elseif (!empty($parameters['prevNodeId']) &&
            $parameters['prevNodeId'] > 0) {
            $prevNode = $this->getService('em')
                ->find('RZ\Roadiz\Core\Entities\Node', (int) $parameters['prevNodeId']);
            if ($prevNode !== null) {
                $node->setPosition($prevNode->getPosition() + 0.5);
            }
        } elseif (!empty($parameters['firstPosition']) &&
            (boolean) $parameters['firstPosition'] === true) {
            $node->setPosition(-0.5);
        } elseif (!empty($parameters['lastPosition']) &&
            (boolean) $parameters['lastPosition'] === true) {
            $node->setPosition(9999999);
        }
        // Apply position update before cleaning
        $this->getService('em')->flush();

        if ($parent !== null) {
            $parent->getHandler()->cleanChildrenPositions();
        } else {
            NodeHandler::cleanRootNodesPositions();
        }

        /*
         * Dispatch event
         */
        $event = new FilterNodeEvent($node);
        $this->getService('dispatcher')->dispatch(NodeEvents::NODE_UPDATED, $event);
    }

    /**
     * Update node's status.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function statusesAction(Request $request)
    {
        /*
         * Validate
         */
        if (true !== $notValid = $this->validateRequest($request)) {
            return new JsonResponse(
                $notValid,
                Response::HTTP_FORBIDDEN,
                ['content-type' => 'application/javascript']
            );
        }

        $this->validateAccessForRole('ROLE_ACCESS_NODES');

        $availableStatuses = [
            'visible' => 'setVisible',
            'status' => 'setStatus',
            'locked' => 'setLocked',
            'hideChildren' => 'setHidingChildren',
            'sterile' => 'setSterile',
        ];

        if ("nodeChangeStatus" == $request->get('_action') &&
            "" != $request->get('statusName')) {
            // just verify role when updating status
            if ($request->get('statusName') == 'status' &&
                $request->get('statusValue') > Node::PENDING &&
                !$this->isGranted('ROLE_ACCESS_NODES_STATUS')) {
                $responseArray = [
                    'statusCode' => Response::HTTP_FORBIDDEN,
                    'status' => 'danger',
                    'responseText' => $this->getTranslator()->trans('role.cannot.update.status'),
                ];

            } else {
                if ($request->get('nodeId') > 0) {
                    $node = $this->getService('em')
                        ->find('RZ\Roadiz\Core\Entities\Node', (int) $request->get('nodeId'));

                    if (null !== $node) {
                        /*
                         * Check if status name is a valid boolean node field.
                         */
                        if (in_array($request->get('statusName'), array_keys($availableStatuses))) {
                            $setter = $availableStatuses[$request->get('statusName')];
                            $value = $request->get('statusValue');

                            if ($this->isGranted('ROLE_ACCESS_NODES_STATUS') ||
                                $request->get('statusName') != 'status') {
                                $node->$setter($value);

                                /*
                                 * If set locked to true,
                                 * need to disable dynamic nodeName
                                 */
                                if ($request->get('statusName') == 'locked' &&
                                    $value === true) {
                                    $node->setDynamicNodeName(false);
                                }

                                $this->em()->flush();

                                /*
                                 * Dispatch event
                                 */
                                $event = new FilterNodeEvent($node);
                                $this->getService('dispatcher')->dispatch(NodeEvents::NODE_UPDATED, $event);

                                if ($request->get('statusName') == 'status') {

                                    $nodeStatuses = [
                                        Node::DRAFT => 'draft',
                                        Node::PENDING => 'pending',
                                        Node::PUBLISHED => 'published',
                                        Node::ARCHIVED => 'archived',
                                        Node::DELETED => 'deleted',
                                    ];
                                    $msg = $this->getTranslator()->trans('node.%name%.status_changed_to.%status%', [
                                        '%name%' => $node->getNodeName(),
                                        '%status%' => $this->getTranslator()->trans($nodeStatuses[$node->getStatus()]),
                                    ]);
                                    $this->publishConfirmMessage($request, $msg, $node->getNodeSources()->first());
                                    $this->getService('dispatcher')->dispatch(NodeEvents::NODE_STATUS_CHANGED, $event);
                                }
                                if ($request->get('statusName') == 'visible') {
                                    $msg = $this->getTranslator()->trans('node.%name%.visibility_changed_to.%visible%', [
                                        '%name%' => $node->getNodeName(),
                                        '%visible%' => $node->isVisible() ? $this->getTranslator()->trans('visible') : $this->getTranslator()->trans('invisible'),
                                    ]);
                                    $this->publishConfirmMessage($request, $msg, $node->getNodeSources()->first());
                                    $this->getService('dispatcher')->dispatch(NodeEvents::NODE_VISIBILITY_CHANGED, $event);
                                }

                                $responseArray = [
                                    'statusCode' => Response::HTTP_OK,
                                    'status' => 'success',
                                    'responseText' => $this->getTranslator()->trans('node.%name%.%field%.updated', [
                                        '%name%' => $node->getNodeName(),
                                        '%field%' => $request->get('statusName'),
                                    ]),
                                    'name' => $request->get('statusName'),
                                    'value' => $value,
                                ];
                            } else {
                                $responseArray = [
                                    'statusCode' => Response::HTTP_FORBIDDEN,
                                    'status' => 'danger',
                                    'responseText' => $this->getTranslator()->trans('role.cannot.update.status'),
                                ];
                            }

                        } else {
                            $responseArray = [
                                'statusCode' => Response::HTTP_FORBIDDEN,
                                'status' => 'danger',
                                'responseText' => $this->getTranslator()->trans('node.has_no.field.%field%', [
                                    '%field%' => $request->get('statusName'),
                                ]),
                            ];
                        }

                    } else {
                        $responseArray = [
                            'statusCode' => Response::HTTP_FORBIDDEN,
                            'status' => 'danger',
                            'responseText' => $this->getTranslator()->trans('node.%nodeId%.not_exists', [
                                '%nodeId%' => $request->get('nodeId'),
                            ]),
                        ];
                    }

                } else {
                    $responseArray = [
                        'statusCode' => Response::HTTP_FORBIDDEN,
                        'status' => 'danger',
                        'responseText' => $this->getTranslator()->trans('node.id.not_specified'),
                    ];
                }
            }

        } else {
            $responseArray = [
                'statusCode' => Response::HTTP_FORBIDDEN,
                'status' => 'danger',
                'responseText' => $this->getTranslator()->trans('node.%nodeId%.not_exists', [
                    '%nodeId%' => $request->get('nodeId'),
                ]),
            ];
        }

        return new JsonResponse(
            $responseArray,
            $responseArray['statusCode'],
            ['content-type' => 'application/javascript']
        );
    }

    public function quickAddAction(Request $request)
    {
        /*
         * Validate
         */
        if (true !== $notValid = $this->validateRequest($request)) {
            return new JsonResponse(
                $notValid,
                Response::HTTP_FORBIDDEN,
                ['content-type' => 'application/javascript']
            );
        }

        $this->validateAccessForRole('ROLE_ACCESS_NODES');

        $responseArray = [];

        try {
            $generator = new UniqueNodeGenerator($this->getService('em'));
            $source = $generator->generateFromRequest($request);

            /*
             * Dispatch event
             */
            $event = new FilterNodeEvent($source->getNode());
            $this->getService('dispatcher')->dispatch(NodeEvents::NODE_CREATED, $event);

            $responseArray = [
                'statusCode' => Response::HTTP_OK,
                'status' => 'success',
                'responseText' => $this->getTranslator()->trans(
                    'added.node.%name%',
                    [
                        '%name%' => $source->getTitle(),
                    ]
                ),
            ];

        } catch (\Exception $e) {
            $msg = $this->getTranslator()->trans($e->getMessage());

            $responseArray = [
                'statusCode' => Response::HTTP_FORBIDDEN,
                'status' => 'danger',
                'responseText' => $msg,
            ];
        }

        return new JsonResponse(
            $responseArray,
            $responseArray['statusCode'],
            ['content-type' => 'application/javascript']
        );
    }
}
