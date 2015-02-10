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
use RZ\Roadiz\Core\Handlers\NodeHandler;
use Themes\Rozier\AjaxControllers\AbstractAjaxController;
use Themes\Rozier\Controllers\NodesController;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

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
            }

            if ($responseArray === null) {
                $responseArray = [
                    'statusCode' => '200',
                    'status' => 'success',
                    'responseText' => $this->getTranslator()->trans('node.%name%.updated', [
                        '%name%' => $node->getNodeName()
                    ])
                ];
            }

            return new Response(
                json_encode($responseArray),
                Response::HTTP_OK,
                ['content-type' => 'application/javascript']
            );
        }


        $responseArray = [
            'statusCode' => '403',
            'status'    => 'danger',
            'responseText' => $this->getTranslator()->trans('node.%nodeId%.not_exists', [
                '%nodeId%' => $nodeId
            ])
        ];

        return new Response(
            json_encode($responseArray),
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
                ['content-type' => 'application/javascript']
            );
        }

        $this->validateAccessForRole('ROLE_ACCESS_NODES');

        $responseArray = null;

        $availableStatuses = [
            'visible' => 'setVisible',
            'status' => 'setStatus',
            'locked' => 'setLocked',
            'hideChildren' => 'setHidingChildren',
            'sterile' => 'setSterile'
        ];

        if ("nodeChangeStatus" == $request->get('_action') &&
            "" != $request->get('statusName')) {
            // just verify role when updating status
            if ($request->get('statusName') == 'status' &&
                $request->get('statusValue') > Node::PENDING &&
                !$this->getService('securityContext')->isGranted('ROLE_ACCESS_NODES_STATUS')) {
                $responseArray = [
                    'statusCode' => Response::HTTP_FORBIDDEN,
                    'status'    => 'danger',
                    'responseText' => $this->getTranslator()->trans('role.cannot.update.status')
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

                            if ($this->getSecurityContext()->isGranted('ROLE_ACCESS_NODES_STATUS') ||
                                $request->get('statusName') != 'status') {
                                $node->$setter($value);

                                /*
                                 * If set locked to true,
                                 * need to disable dynamic nodeName
                                 */
                                if ($request->get('statusName') == 'locked' &&
                                    $value == true) {
                                    $node->setDynamicNodeName(false);
                                }

                                $this->em()->flush();

                                // Update Solr Search engine if setup
                                if (true === $this->getKernel()->pingSolrServer()) {
                                    foreach ($node->getNodeSources() as $nodeSource) {
                                        $solrSource = new \RZ\Roadiz\Core\SearchEngine\SolariumNodeSource(
                                            $nodeSource,
                                            $this->getService('solr')
                                        );
                                        $solrSource->getDocumentFromIndex();
                                        $solrSource->updateAndCommit();
                                    }
                                }

                                $responseArray = [
                                    'statusCode' => Response::HTTP_OK,
                                    'status'    => 'success',
                                    'responseText' => $this->getTranslator()->trans('node.%name%.%field%.updated', [
                                        '%name%' => $node->getNodeName(),
                                        '%field%' => $request->get('statusName')
                                    ]),
                                    'name' => $request->get('statusName'),
                                    'value' => $value
                                ];
                            } else {
                                $responseArray = [
                                    'statusCode' => Response::HTTP_FORBIDDEN,
                                    'status'    => 'danger',
                                    'responseText' => $this->getTranslator()->trans('role.cannot.update.status')
                                ];
                            }


                        } else {
                            $responseArray = [
                                'statusCode' => Response::HTTP_FORBIDDEN,
                                'status'    => 'danger',
                                'responseText' => $this->getTranslator()->trans('node.has_no.field.%field%', [
                                    '%field%' => $request->get('statusName')
                                ])
                            ];
                        }

                    } else {
                        $responseArray = [
                            'statusCode' => Response::HTTP_FORBIDDEN,
                            'status'    => 'danger',
                            'responseText' => $this->getTranslator()->trans('node.%nodeId%.not_exists', [
                                '%nodeId%' => $request->get('nodeId')
                            ])
                        ];
                    }

                } else {
                    $responseArray = [
                        'statusCode' => Response::HTTP_FORBIDDEN,
                        'status'    => 'danger',
                        'responseText' => $this->getTranslator()->trans('node.id.not_specified')
                    ];
                }
            }


        } else {
            $responseArray = [
                'statusCode' => Response::HTTP_FORBIDDEN,
                'status'    => 'danger',
                'responseText' => $this->getTranslator()->trans('node.%nodeId%.not_exists', [
                    '%nodeId%' => $request->get('nodeId')
                ])
            ];
        }

        return new Response(
            json_encode($responseArray),
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
            return new Response(
                json_encode($notValid),
                Response::HTTP_FORBIDDEN,
                ['content-type' => 'application/javascript']
            );
        }

        $this->validateAccessForRole('ROLE_ACCESS_NODES');

        $responseArray = [];

        if ($request->get('nodeTypeId') > 0 &&
            $request->get('parentNodeId') > 0) {
            $nodeType = $this->getService('em')
                            ->find(
                                'RZ\Roadiz\Core\Entities\NodeType',
                                (int) $request->get('nodeTypeId')
                            );

            $parent = $this->getService('em')
                            ->find(
                                'RZ\Roadiz\Core\Entities\Node',
                                (int) $request->get('parentNodeId')
                            );

            if (null !== $nodeType &&
                null !== $parent) {
                if ($request->get('translationId') > 0) {
                    $translation = $this->getService('em')
                                            ->find('RZ\Roadiz\Core\Entities\Translation', (int) $request->get('translationId'));

                } else {
                    $translation = $parent->getNodeSources()->first()->getTranslation();

                    if (null === $translation) {
                        $translation = $this->getService('em')
                                            ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                                            ->findDefault();
                    }
                }

                if ($request->get('tagId') > 0) {
                    $tag = $this->getService('em')
                                ->find('RZ\Roadiz\Core\Entities\Tag', (int) $request->get('tagId'));
                } else {
                    $tag = null;
                }

                try {
                    $source = NodesController::generateUniqueNodeWithTypeAndTranslation($request, $nodeType, $parent, $translation, $tag);

                    $responseArray = [
                        'statusCode' => Response::HTTP_OK,
                        'status'    => 'success',
                        'responseText' => $this->getTranslator()->trans(
                            'added.node.%name%',
                            [
                                '%name%' => $source->getTitle()
                            ]
                        )
                    ];

                } catch (\Exception $e) {
                    $msg = $this->getTranslator()->trans('node.%name%.noCreation.alreadyExists', ['%name%'=>"GeneratedNode"]);

                    $responseArray = [
                        'statusCode' => Response::HTTP_FORBIDDEN,
                        'status'    => 'danger',
                        'responseText' => $msg
                    ];
                }


            } else {
                $responseArray = [
                    'statusCode' => Response::HTTP_FORBIDDEN,
                    'status'    => 'danger',
                    'responseText' => $this->getTranslator()->trans('bad.request')
                ];
            }

        } else {
            $responseArray = [
                'statusCode' => Response::HTTP_FORBIDDEN,
                'status'    => 'danger',
                'responseText' => $this->getTranslator()->trans('bad.request')
            ];
        }

        return new Response(
            json_encode($responseArray),
            $responseArray['statusCode'],
            ['content-type' => 'application/javascript']
        );
    }
}
