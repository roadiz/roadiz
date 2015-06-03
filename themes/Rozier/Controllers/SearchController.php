<?php
/**
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
 * @file SearchController.php
 * @author Maxime Constantinian
 */

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\CMS\Forms\CompareDatetimeType;
use RZ\Roadiz\CMS\Forms\CompareDateType;
use RZ\Roadiz\CMS\Forms\ExtendedBooleanType;
use RZ\Roadiz\CMS\Forms\NodeStatesType;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Utils\XlsxExporter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Themes\Rozier\RozierApp;

class SearchController extends RozierApp
{
    protected $pagination = true;
    protected $itemPerPage = null;

    public function isBlank($var)
    {
        return empty($var) && !is_numeric($var);
    }

    public function notBlank($var)
    {
        return !$this->isBlank($var);
    }

    public function processCriteria($data, $prefix = "")
    {
        if (!empty($data[$prefix . "nodeName"])) {
            $data[$prefix . "nodeName"] = ["LIKE", "%" . $data[$prefix . "nodeName"] . "%"];
        }

        if (isset($data[$prefix . 'parent']) && !$this->isBlank($data[$prefix . "parent"])) {
            if ($data[$prefix . "parent"] == "null" || $data[$prefix . "parent"] == 0) {
                $data[$prefix . "parent"] = null;
            }
        }

        if (isset($data[$prefix . 'visible'])) {
            $data[$prefix . 'visible'] = (bool) $data[$prefix . 'visible'];
        }

        if (isset($data[$prefix . 'createdAt'])) {
            $data[$prefix . "createdAt"] = [
                $data[$prefix . 'createdAt']['compareOp'],
                $data[$prefix . 'createdAt']['compareDatetime'],
            ];
        }

        if (isset($data[$prefix . 'updatedAt'])) {
            $data[$prefix . "updatedAt"] = [
                $data[$prefix . 'updatedAt']['compareOp'],
                $data[$prefix . 'updatedAt']['compareDatetime'],
            ];
        }

        if (isset($data[$prefix . "limitResult"])) {
            $this->pagination = false;
            $this->itemPerPage = $data[$prefix . "limitResult"];
            unset($data[$prefix . "limitResult"]);
        }

        /*
         * no need to prefix tags
         */
        if (isset($data["tags"])) {
            $data["tags"] = explode(',', $data["tags"]);
            foreach ($data["tags"] as $key => $value) {
                $data["tags"][$key] = $this->getService("em")->getRepository("RZ\Roadiz\Core\Entities\Tag")->findByPath($value);
            }
            array_filter($data["tags"]);
        }

        return $data;
    }

    public function processCriteriaNodetype($data, NodeType $nodetype)
    {
        $fields = $nodetype->getFields();
        foreach ($data as $key => $value) {
            foreach ($fields as $field) {
                if ($key == $field->getName()) {
                    if ($field->getType() === NodeTypeField::MARKDOWN_T
                        || $field->getType() === NodeTypeField::STRING_T
                        || $field->getType() === NodeTypeField::TEXT_T
                        || $field->getType() === NodeTypeField::EMAIL_T) {
                        $data[$key] = ["LIKE", "%" . $value . "%"];
                    } elseif ($field->getType() === NodeTypeField::BOOLEAN_T) {
                        $data[$key] = (bool) $value;
                    } elseif ($field->getType() === NodeTypeField::MULTIPLE_T) {
                        $data[$key] = implode(",", $value);
                    } elseif ($field->getType() === NodeTypeField::DATETIME_T) {
                        $data[$key] = [
                            $data[$key]['compareOp'],
                            $data[$key]['compareDatetime'],
                        ];
                    } elseif ($field->getType() === NodeTypeField::DATE_T) {
                        $data[$key] = [
                            $data[$key]['compareOp'],
                            $data[$key]['compareDate'],
                        ];
                    }
                }
            }
        }
        return $data;
    }

    public function searchNodeAction(Request $request)
    {

        $form = $this->buildSimpleForm("")->add("searchANode", "submit", [
            "label" => $this->getTranslator()->trans("search.a.node"),
            "attr" => ["class" => "uk-button uk-button-primary"],
        ])->getForm();
        $form->handleRequest($request);

        $builderNodeType = $this->buildNodeTypeForm();

        $nodeTypeForm = $builderNodeType->getForm();
        $nodeTypeForm->handleRequest($request);

        if (null !== $response = $this->handleNodeTypeForm($nodeTypeForm)) {
            $response->prepare($request);
            return $response->send();
        }

        if ($form->isValid()) {
            $data = [];
            foreach ($form->getData() as $key => $value) {
                if ((!is_array($value) && $this->notBlank($value)) ||
                    (is_array($value) && isset($value["compareDatetime"]))) {
                    $data[$key] = $value;
                }
            }
            $data = $this->processCriteria($data);
            $listManager = $this->createEntityListManager(
                'RZ\Roadiz\Core\Entities\Node',
                $data
            );
            if ($this->pagination === false) {
                $listManager->setItemPerPage($this->itemPerPage);
                $listManager->disablePagination();
            }
            $listManager->handle();

            $this->assignation['filters'] = $listManager->getAssignation();
            $this->assignation['nodes'] = $listManager->getEntities();

        }

        $this->assignation['form'] = $form->createView();
        $this->assignation['nodeTypeForm'] = $nodeTypeForm->createView();
        $this->assignation['filters']['searchDisable'] = true;

        return $this->render('search/list.html.twig', $this->assignation);
    }

    public function searchNodeSourceAction(Request $request, $nodetypeId)
    {
        $nodetype = $this->getService('em')
                         ->find('RZ\Roadiz\Core\Entities\NodeType', $nodetypeId);

        $builder = $this->buildSimpleForm("__node__");
        $builder = $this->extendForm($builder, $nodetype);
        $builder->add("searchANode", "submit", [
            "label" => "search.a.node",
            "attr" => ["class" => "uk-button uk-button-primary"],
        ]);
        $builder->add("exportNodesSources", "submit", [
            "label" => "export.all.nodesSource",
            "attr" => ["class" => "uk-button rz-no-ajax"],
        ]);
        $form = $builder->getForm();
        $form->handleRequest($request);

        $builderNodeType = $this->buildNodeTypeForm($nodetypeId);
        $nodeTypeForm = $builderNodeType->getForm();
        $nodeTypeForm->handleRequest($request);

        if (null !== $response = $this->handleNodeTypeForm($nodeTypeForm)) {
            return $response;
        }

        if (null !== $response = $this->handleNodeForm($form, $nodetype)) {
            return $response;
        }

        $this->assignation['form'] = $form->createView();
        $this->assignation['nodeTypeForm'] = $nodeTypeForm->createView();

        $this->assignation['filters']['searchDisable'] = true;

        return $this->render('search/list.html.twig', $this->assignation);
    }

    /**
     * Build node-type selection form.
     *
     * @param int|null $nodetypeId
     */
    protected function buildNodeTypeForm($nodetypeId = null)
    {
        $builderNodeType = $this->getService('formFactory')
                                ->createNamedBuilder(
                                    'nodeTypeForm',
                                    "form",
                                    [],
                                    ["method" => "get"]
                                );
        $builderNodeType->add(
            "nodetype",
            new \RZ\Roadiz\CMS\Forms\NodeTypesType,
            [
                                'placeholder' => "",
                                'required' => false,
                                'data' => $nodetypeId,
                            ]
        )
                        ->add("nodetypeSubmit", "submit", [
                            "label" => "select.nodetype",
                            "attr" => ["class" => "uk-button uk-button-primary"],
                        ]);

        return $builderNodeType;
    }

    protected function handleNodeTypeForm($nodeTypeForm)
    {
        if ($nodeTypeForm->isValid()) {
            if (empty($nodeTypeForm->getData()['nodetype'])) {
                return $this->redirect($this->generateUrl('searchNodePage'));
            } else {
                return $this->redirect($this->generateUrl(
                    'searchNodeSourcePage',
                    [
                        "nodetypeId" => $nodeTypeForm->getData()['nodetype'],
                    ]
                ));
            }

            return $response;
        }

        return null;
    }

    protected function handleNodeForm($form, NodeType $nodetype)
    {
        if ($form->isValid()) {
            $data = [];
            foreach ($form->getData() as $key => $value) {
                if ((!is_array($value) && $this->notBlank($value))
                    || (is_array($value) && isset($value["compareDatetime"]))
                    || (is_array($value) && isset($value["compareDate"]))
                    || (is_array($value) && $value != [] && !isset($value["compareOp"]))) {
                    if (strstr($key, "__node__") == 0) {
                        $data[str_replace("__node__", "node.", $key)] = $value;
                    } else {
                        $data[$key] = $value;
                    }
                }
            }
            $data = $this->processCriteria($data, "node.");
            $data = $this->processCriteriaNodetype($data, $nodetype);

            $listManager = $this->createEntityListManager(
                NodeType::getGeneratedEntitiesNamespace() . '\\' . $nodetype->getSourceEntityClassName(),
                $data
            );
            if ($this->pagination === false) {
                $listManager->setItemPerPage($this->itemPerPage);
                $listManager->disablePagination();
            }
            $listManager->handle();
            $entities = $listManager->getEntities();
            $nodes = [];
            foreach ($entities as $nodesSource) {
                if (!in_array($nodesSource->getNode(), $nodes)) {
                    $nodes[] = $nodesSource->getNode();
                }
            }

            if ($form->get('exportNodesSources')->isClicked()) {
                $response = new Response(
                    $this->getXlsxResults($nodetype, $entities),
                    Response::HTTP_OK,
                    []
                );

                $response->headers->set(
                    'Content-Disposition',
                    $response->headers->makeDisposition(
                        ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                        'search.xlsx'
                    )
                );

                return $response;
            }

            $this->assignation['filters'] = $listManager->getAssignation();
            $this->assignation['nodesSources'] = $entities;
            $this->assignation['nodes'] = $nodes;
        }

        return null;
    }

    protected function getXlsxResults(NodeType $nodetype, array $entities = [])
    {
        $fields = $nodetype->getFields();
        $keys = [];
        $answers = [];
        $keys[] = "title";
        foreach ($fields as $field) {
            if (!$field->isVirtual()) {
                $keys[] = $field->getName();
            }
        }
        foreach ($entities as $idx => $nodesSource) {
            $array = [];
            foreach ($keys as $key) {
                $getter = 'get' . str_replace('_', '', ucwords($key));
                $tmp = $nodesSource->$getter();
                if (is_array($tmp)) {
                    $tmp = implode(',', $tmp);
                }
                $array[] = $tmp;
            }
            $answers[$idx] = $array;
        }

        return XlsxExporter::exportXlsx($answers, $keys);
    }

    public function buildSimpleForm($prefix)
    {
        $builder = $this->getService('formFactory')
                        ->createBuilder(
                            'form',
                            [],
                            ["method" => "get"]
                        )
                        ->add($prefix . 'status', new NodeStatesType(), [
                            'label' => 'node.status',
                            'required' => false,
                        ])
                        ->add($prefix . 'visible', new ExtendedBooleanType(), [
                            'label' => 'visible',
                        ])
                        ->add($prefix . 'locked', new ExtendedBooleanType(), [
                            'label' => 'locked',
                        ])
                        ->add($prefix . 'sterile', new ExtendedBooleanType(), [
                            'label' => 'sterile-status',
                        ])
                        ->add($prefix . 'hideChildren', new ExtendedBooleanType(), [
                            'label' => 'hiding-children',
                        ])
                        ->add($prefix . 'nodeName', 'text', [
                            'label' => 'nodeName',
                            'required' => false,
                        ])
                        ->add($prefix . 'parent', 'text', [
                            'label' => 'node.id.parent',
                            'required' => false,
                        ])
                        ->add($prefix . "createdAt", new CompareDatetimeType(), [
                            'label' => 'created.at',
                            'virtual' => false,
                            'required' => false,
                        ])
                        ->add($prefix . "updatedAt", new CompareDatetimeType(), [
                            'label' => 'updated.at',
                            'virtual' => false,
                            'required' => false,
                        ])
                        ->add($prefix . "limitResult", "number", [
                            'label' => 'node.limit.result',
                            'required' => false,
                            'constraints' => [
                                new GreaterThan(0),
                            ],
                        ])
                        // No need to prefix tags
                        ->add('tags', 'text', [
                            'label' => 'node.tags',
                            'required' => false,
                            'attr' => ["class" => "rz-tag-autocomplete"],
                        ])
                        // No need to prefix tags
                        ->add('tagExclusive', 'checkbox', [
                            'label' => 'node.tag.exclusive',
                            'required' => false,
                        ]);

        return $builder;
    }

    private function extendForm($builder, $nodetype)
    {
        $fields = $nodetype->getFields();

        $builder->add(
            "nodetypefield",
            new \RZ\Roadiz\CMS\Forms\SeparatorType(),
            [
                'label' => 'nodetypefield',
                'attr' => ["class" => "label-separator"],
            ]
        );

        foreach ($fields as $field) {
            $option = ["label" => $field->getLabel()];
            $option['required'] = false;
            if ($field->isVirtual()) {
                continue;
            }

            if ($field->getType() === NodeTypeField::ENUM_T) {
                $choices = explode(',', $field->getDefaultValues());
                $choices = array_combine(array_values($choices), array_values($choices));
                $type = "choice";
                $option['placeholder'] = 'ignore';
                $option['required'] = false;
                $option["expanded"] = false;
                if (count($choices) < 4) {
                    $option["expanded"] = true;
                }
                $option["choices"] = $choices;
            } elseif ($field->getType() === NodeTypeField::MULTIPLE_T) {
                $choices = explode(',', $field->getDefaultValues());
                $choices = array_combine(array_values($choices), array_values($choices));
                $type = "choice";
                $option["choices"] = $choices;
                $option['placeholder'] = 'ignore';
                $option['required'] = false;
                $option["multiple"] = true;
                $option["expanded"] = false;
                if (count($choices) < 4) {
                    $option["expanded"] = true;
                }
            } elseif ($field->getType() === NodeTypeField::DATETIME_T) {
                $type = new CompareDatetimeType();
            } elseif ($field->getType() === NodeTypeField::DATE_T) {
                $type = new CompareDateType();
            } else {
                $type = NodeTypeField::$typeToForm[$field->getType()];
            }

            if ($field->getType() === NodeTypeField::MARKDOWN_T ||
                $field->getType() === NodeTypeField::STRING_T ||
                $field->getType() === NodeTypeField::TEXT_T ||
                $field->getType() === NodeTypeField::EMAIL_T
            ) {
                $type = "text";
            }

            $builder->add($field->getName(), $type, $option);
        }
        return $builder;
    }
}
