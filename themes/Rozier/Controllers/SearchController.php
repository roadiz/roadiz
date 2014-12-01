<?php
/**
 * Copyright © 2014, REZO ZERO
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
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file SearchController.php
 * @copyright REZO ZERO 2014
 * @author Maxime Constantinian
 */

namespace Themes\Rozier\Controllers;

use Themes\Rozier\RozierApp;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\ListManagers\EntityListManager;
use RZ\Roadiz\Core\Utils\XlsxExporter;

use RZ\Roadiz\CMS\Forms\NodeStatesType;
use RZ\Roadiz\CMS\Forms\CompareDatetimeType;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

use Symfony\Component\Validator\Constraints\GreaterThan;

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
        if (!empty($data[$prefix."nodeName"])) {
            $data[$prefix."nodeName"] = array("LIKE", "%" . $data[$prefix."nodeName"] . "%");
        }

        if (isset($data[$prefix.'parent']) && !$this->isBlank($data[$prefix."parent"])) {
            if ($data[$prefix."parent"] == "null" || $data[$prefix."parent"] == 0) {
                $data[$prefix."parent"] = null;
            }
        }

        if (isset($data[$prefix.'visible'])) {
            $data[$prefix.'visible'] = (bool) $data[$prefix.'visible'];
        }

        if (isset($data[$prefix.'createdAt'])) {
            $data[$prefix."createdAt"] = array(
                $data[$prefix.'createdAt']['compareOp'],
                $data[$prefix.'createdAt']['compareDatetime']
            );
            //unset($data[$prefix.'createdAt']);
        }

        if (isset($data[$prefix.'updatedAt'])) {
            $data[$prefix."updatedAt"] = array(
                $data[$prefix.'updatedAt']['compareOp'],
                $data[$prefix.'updatedAt']['compareDatetime']
            );
            //unset($data[$prefix.'updatedAt']);
        }

        if (isset($data[$prefix."limitResult"])) {
            $this->pagination = false;
            $this->itemPerPage = $data[$prefix."limitResult"];
            unset($data[$prefix."limitResult"]);
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

    public function processCriteriaNodetype($data, $nodetype)
    {
        $fields = $nodetype->getFields();
        foreach ($data as $key => $value) {
            foreach ($fields as $field) {
                if ($key == $field->getName()) {
                    if ($field->getType() === NodeTypeField::MARKDOWN_T
                        || $field->getType() === NodeTypeField::STRING_T
                        || $field->getType() === NodeTypeField::TEXT_T
                        || $field->getType() === NodeTypeField::EMAIL_T) {
                        $data[$key] = array("LIKE", "%" . $value . "%");
                    }
                    if ($field->getType() === NodeTypeField::BOOLEAN_T) {
                        $data[$key] = (bool) $value;
                    }
                    if ($field->getType() === NodeTypeField::MULTIPLE_T) {
                        $data[$key] = implode(",", $value);
                    }
                    if ($field->getType() == NodeTypeField::DATETIME_T) {
                        $data[$key] = array(
                            $data[$key]['compareOp'],
                            $data[$key]['compareDatetime']
                        );
                        //unset($data[$key]);
                    }
                }
            }
        }
        return $data;
    }

    public function searchNodeAction(Request $request)
    {

        $form = $this->buildSimpleForm("")->add("searchANode", "submit", array(
            "label" => $this->getTranslator()->trans("search.a.node"),
            "attr" => array("class" => "uk-button uk-button-primary")
        ))->getForm();
        $form->handleRequest();

        $builderNodeType = $this->getService('formFactory')
                                ->createNamedBuilder(
                                    'nodeTypeForm',
                                    "form",
                                    array(),
                                    array("method" => "get")
                                );
        $builderNodeType->add(
            "nodetype",
            new \RZ\Roadiz\CMS\Forms\NodeTypesType,
            array(
                'empty_value' => "",
                'required' => false
            )
        )
        ->add("nodetypeSubmit", "submit", array(
            "label" => $this->getTranslator()->trans("select.nodetype"),
            "attr" => array("class" => "uk-button uk-button-primary")
        ));

        $nodeTypeForm = $builderNodeType->getForm();
        $nodeTypeForm->handleRequest();

        if ($nodeTypeForm->isValid()) {
            if (empty($nodeTypeForm->getData()['nodetype'])) {
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate('searchNodePage')
                );
                $response->prepare($request);
            } else {
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate(
                        'searchNodeSourcePage',
                        array("nodetypeId" => $nodeTypeForm->getData()['nodetype'])
                    )
                );
                $response->prepare($request);
            }

            return $response->send();
        }

        if ($form->isValid()) {

            // $data = array_filter($form->getData(), $this->isBlank);
            $data = array();
            foreach ($form->getData() as $key => $value) {
                if ((!is_array($value) && $this->notBlank($value)) ||
                    (is_array($value) && isset($value["compareDatetime"]))) {
                    $data[$key] = $value;
                }
            }
            $data = $this->processCriteria($data);
            $listManager = new EntityListManager(
                $request,
                $this->getService('em'),
                'RZ\Roadiz\Core\Entities\Node',
                $data
            );
            if ($this->pagination == false) {
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

        return new Response(
            $this->getTwig()->render('search/list.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    public function searchNodeSourceAction(Request $request, $nodetypeId)
    {

        $nodetype = $this->getService('em')
                         ->find('RZ\Roadiz\Core\Entities\NodeType', $nodetypeId);

        $builder = $this->buildSimpleForm("__node__");
        $builder = $this->extendForm($builder, $nodetype);
        $builder->add("searchANode", "submit", array(
            "label" => $this->getTranslator()->trans("search.a.node"),
            "attr" => array("class" => "uk-button uk-button-primary")
        ));
        $builder->add("exportNodesSources", "submit", array(
            "label" => $this->getTranslator()->trans("export.all.nodesSource"),
            "attr" => array("class" => "uk-button rz-no-ajax")
        ));
        $form = $builder->getForm();
        $form->handleRequest();

        $builderNodeType = $this->getService('formFactory')
                                ->createNamedBuilder(
                                    'nodeTypeForm',
                                    "form",
                                    array(),
                                    array("method" => "get")
                                );
        $builderNodeType->add(
            "nodetype",
            new \RZ\Roadiz\CMS\Forms\NodeTypesType,
            array(
                  'empty_value' => "",
                  'required' => false,
                  'data' => $nodetypeId
            )
        )
        ->add("nodetypeSubmit", "submit", array(
            "label" => $this->getTranslator()->trans("select.nodetype"),
            "attr" => array("class" => "uk-button uk-button-primary")
        ));

        $nodeTypeForm = $builderNodeType->getForm();
        $nodeTypeForm->handleRequest();

        if ($nodeTypeForm->isValid()) {
            if (empty($nodeTypeForm->getData()['nodetype'])) {
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate('searchNodePage')
                );
                $response->prepare($request);
            } else {
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate(
                        'searchNodeSourcePage',
                        array(
                            "nodetypeId" => $nodeTypeForm->getData()['nodetype']
                        )
                    )
                );
                $response->prepare($request);
            }
            return $response->send();
        }

        if ($form->isValid()) {

            $data = array();
            foreach ($form->getData() as $key => $value) {

                if ((!is_array($value) && $this->notBlank($value))
                    || (is_array($value) && isset($value["compareDatetime"]))
                    || (is_array($value) && $value != array() && !isset($value["compareOp"]))) {
                    if (strstr($key, "__node__") == 0) {
                        $data[str_replace("__node__", "node.", $key)] = $value;
                    } else {
                        $data[$key] = $value;
                    }
                }
            }

            $data = $this->processCriteria($data, "node.");
            $data = $this->processCriteriaNodetype($data, $nodetype);

            $listManager = new EntityListManager(
                $request,
                $this->getService('em'),
                NodeType::getGeneratedEntitiesNamespace().'\\'.$nodetype->getSourceEntityClassName(),
                $data
            );
            if ($this->pagination == false) {
                $listManager->setItemPerPage($this->itemPerPage);
                $listManager->disablePagination();
            }
            $listManager->handle();
            $this->assignation['filters'] = $listManager->getAssignation();
            $this->assignation['nodesSources'] = $listManager->getEntities();
            $nodes = array();
            foreach ($listManager->getEntities() as $nodesSource) {
                $nodes[] = $nodesSource->getNode();
            }
            $this->assignation['nodes'] = $nodes;

            if ($form->get('exportNodesSources')->isClicked()) {
                $fields = $nodetype->getFields();
                $keys = array();
                $answers = array();
                $keys[] = "title";
                foreach ($fields as $field) {
                    if (!$field->isVirtual()) {
                        $keys[] = $field->getName();
                    }
                }
                foreach ($listManager->getEntities() as $idx => $nodesSource) {
                    $array = array();
                    foreach ($keys as $key) {
                        //$getter = 'get'.ucwords($key);
                        $getter = 'get'.str_replace('_', '', ucwords($key));
                        $tmp = $nodesSource->$getter();
                        if (is_array($tmp)) {
                            $tmp = implode(',', $tmp);
                        }
                        $array[] = $tmp;
                    }
                    $answers[$idx] = $array;
                }
                $xlsx = XlsxExporter::exportXlsx($answers, $keys);

                $response = new Response(
                    $xlsx,
                    Response::HTTP_OK,
                    array()
                );

                $response->headers->set(
                    'Content-Disposition',
                    $response->headers->makeDisposition(
                        ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                        'search.xlsx'
                    )
                );

                $response->prepare($request);

                return $response;
            }
        }



        $this->assignation['form'] = $form->createView();
        $this->assignation['nodeTypeForm'] = $nodeTypeForm->createView();

        $this->assignation['filters']['searchDisable'] = true;

        return new Response(
            $this->getTwig()->render('search/list.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    public function buildSimpleForm($prefix)
    {
        $builder = $this->getService('formFactory')
            ->createBuilder(
                'form',
                array(),
                array("method" => "get")
            )
            ->add($prefix.'status', new NodeStatesType(), array(
                'label' => $this->getTranslator()->trans('node.status'),
                'required' => false
            ))
            ->add($prefix.'visible', 'choice', array(
                'label' => $this->getTranslator()->trans('visible'),
                'choices' => array(true => $this->getTranslator()->trans('true'), false => $this->getTranslator()->trans('false')),
                'empty_value' => $this->getTranslator()->trans('ignore'),
                'required' => false,
                'expanded' => true
            ))
            ->add($prefix.'locked', 'choice', array(
                'label' => $this->getTranslator()->trans('locked'),
                'choices' => array(true => $this->getTranslator()->trans('true'), false => $this->getTranslator()->trans('false')),
                'empty_value' => $this->getTranslator()->trans('ignore'),
                'required' => false,
                'expanded' => true
            ))
            ->add($prefix.'sterile', 'choice', array(
                'label' => $this->getTranslator()->trans('sterile-status'),
                'choices' => array(true => $this->getTranslator()->trans('true'), false => $this->getTranslator()->trans('false')),
                'empty_value' => $this->getTranslator()->trans('ignore'),
                'required' => false,
                'expanded' => true
            ))
            ->add($prefix.'hideChildren', 'choice', array(
                'label' => $this->getTranslator()->trans('hiding-children'),
                'choices' => array(true => $this->getTranslator()->trans('true'), false => $this->getTranslator()->trans('false')),
                'empty_value' => $this->getTranslator()->trans('ignore'),
                'required' => false,
                'expanded' => true
            ))
            ->add($prefix.'nodeName', 'text', array(
                'label' => $this->getTranslator()->trans('nodeName'),
                'required' => false
            ))
            ->add($prefix.'parent', 'text', array(
                'label' => $this->getTranslator()->trans('node.id.parent'),
                'required' => false
            ))
            ->add($prefix."createdAt", new CompareDatetimeType($this->getTranslator()), array(
                'label' => $this->getTranslator()->trans('created.at'),
                'virtual' => false,
                'required' => false
            ))
            ->add($prefix."updatedAt", new CompareDatetimeType($this->getTranslator()), array(
                'label' => $this->getTranslator()->trans('updated.at'),
                'virtual' => false,
                'required' => false
            ))
            ->add($prefix."limitResult", "number", array(
                'label' => $this->getTranslator()->trans('node.limit.result'),
                'required' => false,
                'constraints' => array(
                           new GreaterThan(0)
                       ),
            ))
            // No need to prefix tags
            ->add('tags', 'text', array(
                'label' => $this->getTranslator()->trans('node.tags'),
                'required' => false,
                'attr' => array ("class" => "rz-tag-autocomplete")
            ))
            // No need to prefix tags
            ->add('tagExclusive', 'checkbox', array(
                'label' => $this->getTranslator()->trans('node.tag.exclusive'),
                'required' => false
            ));


        return $builder;
    }

    private function extendForm($builder, $nodetype)
    {
        $fields = $nodetype->getFields();

        $builder->add(
            "nodetypefield",
            new \RZ\Roadiz\CMS\Forms\SeparatorType(),
            array(
                'label' => $this->getTranslator()->trans('nodetypefield'),
                'attr' => array("class" => "label-separator")
            )
        );

        foreach ($fields as $field) {
            $option = array("label" => $field->getLabel());
            $type = null;
            $option['required'] = false;
            if ($field->isVirtual()) {
                continue;
            }

            if (NodeTypeField::$typeToForm[$field->getType()] == "enumeration") {
                $choices = explode(',', $field->getDefaultValues());
                $choices = array_combine(array_values($choices), array_values($choices));
                $type = "choice";
                $option['empty_value'] = $this->getTranslator()->trans('ignore');
                $option['required'] = false;
                $option["expanded"] = false;
                if (count($choices) < 4) {
                    $option["expanded"] = true;
                }
                $option["choices"] = $choices;
            } elseif (NodeTypeField::$typeToForm[$field->getType()] == "multiple_enumeration") {
                $choices = explode(',', $field->getDefaultValues());
                $choices = array_combine(array_values($choices), array_values($choices));
                $type = "choice";
                $option["choices"] = $choices;
                $option['empty_value'] = $this->getTranslator()->trans('ignore');
                $option['required'] = false;
                $option["multiple"] = true;
                $option["expanded"] = false;
                if (count($choices) < 4) {
                    $option["expanded"] = true;
                }
            } elseif (NodeTypeField::$typeToForm[$field->getType()] == "datetime") {
                $type = new CompareDatetimeType($this->getTranslator());
            } else {
                $type = NodeTypeField::$typeToForm[$field->getType()];
            }

            if (
                $field->getType() === NodeTypeField::MARKDOWN_T ||
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
