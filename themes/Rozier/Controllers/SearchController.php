<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers;

use DateTime;
use IteratorAggregate;
use RZ\Roadiz\CMS\Forms\CompareDatetimeType;
use RZ\Roadiz\CMS\Forms\CompareDateType;
use RZ\Roadiz\CMS\Forms\ExtendedBooleanType;
use RZ\Roadiz\CMS\Forms\NodeSource\NodeSourceType;
use RZ\Roadiz\CMS\Forms\NodeStatesType;
use RZ\Roadiz\CMS\Forms\NodeTypesType;
use RZ\Roadiz\CMS\Forms\SeparatorType;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Utils\XlsxExporter;
use Symfony\Component\Form\ClickableInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Themes\Rozier\RozierApp;

/**
 * @package Themes\Rozier\Controllers
 */
class SearchController extends RozierApp
{
    protected bool $pagination = true;
    protected ?int $itemPerPage = null;

    /**
     * @param mixed $var
     * @return bool
     */
    public function isBlank($var)
    {
        return empty($var) && !is_numeric($var);
    }

    /**
     * @param mixed $var
     * @return bool
     */
    public function notBlank($var)
    {
        return !$this->isBlank($var);
    }

    /**
     * @param array  $data
     * @param string $fieldName
     *
     * @return array
     */
    protected function appendDateTimeCriteria(array &$data, string $fieldName)
    {
        $date = $data[$fieldName]['compareDatetime'];
        if ($date instanceof DateTime) {
            $date = $date->format('Y-m-d H:i:s');
        }
        $data[$fieldName] = [
            $data[$fieldName]['compareOp'],
            $date,
        ];
        return $data;
    }

    /**
     * @param array $data
     * @param string $prefix
     * @return mixed
     */
    protected function processCriteria($data, string $prefix = "")
    {
        if (!empty($data[$prefix . "nodeName"])) {
            if (isset($data[$prefix . "nodeName_exact"]) && $data[$prefix . "nodeName_exact"] === true) {
                $data[$prefix . "nodeName"] = $data[$prefix . "nodeName"];
            } else {
                $data[$prefix . "nodeName"] = ["LIKE", "%" . $data[$prefix . "nodeName"] . "%"];
            }
        }

        if (key_exists($prefix . "nodeName_exact", $data)) {
            unset($data[$prefix . "nodeName_exact"]);
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
            $this->appendDateTimeCriteria($data, $prefix . 'createdAt');
        }

        if (isset($data[$prefix . 'updatedAt'])) {
            $this->appendDateTimeCriteria($data, $prefix . 'updatedAt');
        }

        if (isset($data[$prefix . "limitResult"])) {
            $this->pagination = false;
            $this->itemPerPage = (int) $data[$prefix . "limitResult"];
            unset($data[$prefix . "limitResult"]);
        }

        /*
         * no need to prefix tags
         */
        if (isset($data["tags"])) {
            $data["tags"] = array_map('trim', explode(',', $data["tags"]));
            foreach ($data["tags"] as $key => $value) {
                $data["tags"][$key] = $this->get("em")->getRepository(Tag::class)->findByPath($value);
            }
            array_filter($data["tags"]);
        }

        return $data;
    }

    /**
     * @param array|\Traversable $data
     * @param NodeType $nodetype
     * @return mixed
     */
    protected function processCriteriaNodetype($data, NodeType $nodetype)
    {
        $fields = $nodetype->getFields();
        foreach ($data as $key => $value) {
            if ($key === 'title') {
                $data['title'] = ["LIKE", "%" . $value . "%"];
                if (isset($data[$key . '_exact'])) {
                    if ($data[$key . '_exact'] === true) {
                        $data['title'] = $value;
                    }
                }
            } elseif ($key === 'publishedAt') {
                $this->appendDateTimeCriteria($data, 'publishedAt');
            } else {
                /** @var NodeTypeField $field */
                foreach ($fields as $field) {
                    if ($key == $field->getName()) {
                        if ($field->getType() === NodeTypeField::MARKDOWN_T
                            || $field->getType() === NodeTypeField::STRING_T
                            || $field->getType() === NodeTypeField::YAML_T
                            || $field->getType() === NodeTypeField::JSON_T
                            || $field->getType() === NodeTypeField::TEXT_T
                            || $field->getType() === NodeTypeField::EMAIL_T
                            || $field->getType() === NodeTypeField::CSS_T) {
                            $data[$field->getVarName()] = ["LIKE", "%" . $value . "%"];
                            if (isset($data[$key . '_exact']) && $data[$key . '_exact'] === true) {
                                $data[$field->getVarName()] = $value;
                            }
                        } elseif ($field->getType() === NodeTypeField::BOOLEAN_T) {
                            $data[$field->getVarName()] = (bool) $value;
                        } elseif ($field->getType() === NodeTypeField::MULTIPLE_T) {
                            $data[$field->getVarName()] = implode(",", $value);
                        } elseif ($field->getType() === NodeTypeField::DATETIME_T) {
                            $this->appendDateTimeCriteria($data, $key);
                        } elseif ($field->getType() === NodeTypeField::DATE_T) {
                            $this->appendDateTimeCriteria($data, $key);
                        }
                    }
                }
            }
            if (key_exists($key . '_exact', $data)) {
                unset($data[$key . '_exact']);
            }
        }
        return $data;
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function searchNodeAction(Request $request)
    {
        $builder = $this->buildSimpleForm('');
        $form = $this->addButtons($builder)->getForm();
        $form->handleRequest($request);

        $builderNodeType = $this->buildNodeTypeForm();

        /** @var Form $nodeTypeForm */
        $nodeTypeForm = $builderNodeType->getForm();
        $nodeTypeForm->handleRequest($request);

        if (null !== $response = $this->handleNodeTypeForm($nodeTypeForm)) {
            $response->prepare($request);
            return $response->send();
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $data = [];
            foreach ($form->getData() as $key => $value) {
                if ((!is_array($value) && $this->notBlank($value)) ||
                    (is_array($value) && isset($value["compareDatetime"]))) {
                    $data[$key] = $value;
                }
            }
            $data = $this->processCriteria($data);
            $listManager = $this->createEntityListManager(
                Node::class,
                $data
            );
            $listManager->setDisplayingNotPublishedNodes(true);
            $listManager->setDisplayingAllNodesStatuses(true);

            if ($this->pagination === false) {
                $listManager->setItemPerPage($this->itemPerPage ?? 999);
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

    /**
     * @param Request $request
     * @param int $nodetypeId
     *
     * @return null|RedirectResponse|Response
     */
    public function searchNodeSourceAction(Request $request, int $nodetypeId)
    {
        /** @var NodeType|null $nodetype */
        $nodetype = $this->get('em')->find(NodeType::class, $nodetypeId);

        $builder = $this->buildSimpleForm("__node__");
        $this->extendForm($builder, $nodetype);
        $this->addButtons($builder, true);

        /** @var Form $form */
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
        $this->assignation['nodeType'] = $nodetype;
        $this->assignation['filters']['searchDisable'] = true;

        return $this->render('search/list.html.twig', $this->assignation);
    }

    /**
     * Build node-type selection form.
     *
     * @param int|null $nodetypeId
     * @return FormBuilder
     */
    protected function buildNodeTypeForm(?int $nodetypeId = null)
    {
        /** @var FormBuilder $builderNodeType */
        $builderNodeType = $this->get('formFactory')
                                ->createNamedBuilder(
                                    'nodeTypeForm',
                                    FormType::class,
                                    [],
                                    ["method" => "get"]
                                );
        $builderNodeType->add(
            "nodetype",
            NodeTypesType::class,
            [
                'label' => 'nodeType',
                'placeholder' => "ignore",
                'required' => false,
                'data' => $nodetypeId,
                'showInvisible' => true,
            ]
        );

        return $builderNodeType;
    }

    /**
     * @param FormBuilder $builder
     * @param bool $exportXlsx
     *
     * @return FormBuilder
     */
    protected function addButtons(FormBuilder $builder, bool $exportXlsx = false)
    {
        $builder->add('search', SubmitType::class, [
            'label' => 'search.a.node',
            'attr' => [
                'class' => 'uk-button uk-button-primary',
            ],
        ]);

        if ($exportXlsx) {
            $builder->add('export', SubmitType::class, [
                'label' => 'export.all.nodesSource',
                'attr' => [
                    'class' => 'uk-button rz-no-ajax',
                ],
            ]);
        }

        return $builder;
    }

    /**
     * @param FormInterface $nodeTypeForm
     *
     * @return null|RedirectResponse
     */
    protected function handleNodeTypeForm(FormInterface $nodeTypeForm)
    {
        if ($nodeTypeForm->isSubmitted() && $nodeTypeForm->isValid()) {
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
        }

        return null;
    }

    /**
     * @param FormInterface $form
     * @param NodeType $nodetype
     *
     * @return null|Response
     */
    protected function handleNodeForm(FormInterface $form, NodeType $nodetype)
    {
        if ($form->isSubmitted() && $form->isValid()) {
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
                $nodetype->getSourceEntityFullQualifiedClassName(),
                $data
            );
            $listManager->setDisplayingNotPublishedNodes(true);
            $listManager->setDisplayingAllNodesStatuses(true);
            if ($this->pagination === false) {
                $listManager->setItemPerPage($this->itemPerPage ?? 999);
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
            /*
             * Export all entries into XLSX format
             */
            $button = $form->get('export');
            if ($button instanceof ClickableInterface && $button->isClicked()) {
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

    /**
     * @param NodeType                $nodetype
     * @param array|IteratorAggregate $entities
     *
     * @return string
     */
    protected function getXlsxResults(NodeType $nodetype, $entities)
    {
        $fields = $nodetype->getFields();
        $keys = [];
        $answers = [];
        $keys[] = "title";
        /** @var NodeTypeField $field */
        foreach ($fields as $field) {
            if (!$field->isVirtual() && !$field->isCollection()) {
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

        $exporter = new XlsxExporter($this->get('translator'));
        return $exporter->exportXlsx($answers, $keys);
    }

    /**
     * @param string $prefix
     * @return FormBuilder
     */
    protected function buildSimpleForm(string $prefix = '')
    {
        /** @var FormBuilder $builder */
        $builder = $this->createFormBuilder([], ["method" => "get"]);

        $builder->add($prefix . 'status', NodeStatesType::class, [
            'label' => 'node.status',
            'required' => false,
        ]);
        $builder->add(
            $builder->create('status_group', FormType::class, [
                'label' => false,
                'inherit_data' => true,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-col-status-group',
                ],
            ])
            ->add($prefix . 'visible', ExtendedBooleanType::class, [
                'label' => 'visible',
            ])
            ->add($prefix . 'locked', ExtendedBooleanType::class, [
                'label' => 'locked',
            ])
            ->add($prefix . 'sterile', ExtendedBooleanType::class, [
                'label' => 'sterile-status',
            ])
            ->add($prefix . 'hideChildren', ExtendedBooleanType::class, [
                'label' => 'hiding-children',
            ])
        );
        $builder->add(
            $this->createTextSearchForm($builder, $prefix . 'nodeName', 'nodeName')
        );
        $builder->add($prefix . 'parent', TextType::class, [
                'label' => 'node.id.parent',
                'required' => false,
            ])
            ->add($prefix . 'createdAt', CompareDatetimeType::class, [
                'label' => 'created.at',
                'inherit_data' => false,
                'required' => false,
            ])
            ->add($prefix . 'updatedAt', CompareDatetimeType::class, [
                'label' => 'updated.at',
                'inherit_data' => false,
                'required' => false,
            ])
            ->add($prefix . 'limitResult', NumberType::class, [
                'label' => 'node.limit.result',
                'required' => false,
                'constraints' => [
                    new GreaterThan(0),
                ],
            ])
            // No need to prefix tags
            ->add('tags', TextType::class, [
                'label' => 'node.tags',
                'required' => false,
                'attr' => ['class' => 'rz-tag-autocomplete'],
            ])
            // No need to prefix tags
            ->add('tagExclusive', CheckboxType::class, [
                'label' => 'node.tag.exclusive',
                'required' => false,
            ])
        ;

        return $builder;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param string               $formName
     * @param string               $label
     *
     * @return FormBuilderInterface
     */
    protected function createTextSearchForm(
        FormBuilderInterface $builder,
        string $formName,
        string $label
    ): FormBuilderInterface {
        return $builder->create($formName . '_group', FormType::class, [
                'label' => false,
                'inherit_data' => true,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-col-search-group',
                ],
            ])
            ->add($formName, TextType::class, [
                'label' => $label,
                'required' => false,
            ])
            ->add($formName . '_exact', CheckboxType::class, [
                'label' => 'exact_search',
                'required' => false,
            ])
        ;
    }

    /**
     * @param FormBuilder $builder
     * @param NodeType $nodetype
     * @return FormBuilder
     */
    private function extendForm(FormBuilder $builder, NodeType $nodetype)
    {
        $fields = $nodetype->getFields();

        $builder->add(
            "nodetypefield",
            SeparatorType::class,
            [
                'label' => 'nodetypefield',
                'attr' => ["class" => "label-separator"],
            ]
        );
        $builder->add(
            $this->createTextSearchForm($builder, 'title', 'title')
        );
        if ($nodetype->isPublishable()) {
            $builder->add(
                "publishedAt",
                CompareDatetimeType::class,
                [
                    'label' => 'publishedAt',
                    'required' => false,
                ]
            );
        }


        /** @var NodeTypeField $field */
        foreach ($fields as $field) {
            $option = ["label" => $field->getLabel()];
            $option['required'] = false;
            if ($field->isVirtual()) {
                continue;
            }
            /*
             * Prevent searching on complex fields
             */
            if ($field->isMultipleProvider() ||
                $field->isSingleProvider() ||
                $field->isCollection() ||
                $field->isManyToMany() ||
                $field->isManyToOne()) {
                continue;
            }

            if ($field->getType() === NodeTypeField::ENUM_T) {
                $choices = explode(',', $field->getDefaultValues() ?? '');
                $choices = array_map('trim', $choices);
                $choices = array_combine(array_values($choices), array_values($choices));
                $type = ChoiceType::class;
                $option['placeholder'] = 'ignore';
                $option['required'] = false;
                $option["expanded"] = false;
                if (count($choices) < 4) {
                    $option["expanded"] = true;
                }
                $option["choices"] = $choices;
            } elseif ($field->getType() === NodeTypeField::MULTIPLE_T) {
                $choices = explode(',', $field->getDefaultValues() ?? '');
                $choices = array_map('trim', $choices);
                $choices = array_combine(array_values($choices), array_values($choices));
                $type = ChoiceType::class;
                $option["choices"] = $choices;
                $option['placeholder'] = 'ignore';
                $option['required'] = false;
                $option["multiple"] = true;
                $option["expanded"] = false;
                if (count($choices) < 4) {
                    $option["expanded"] = true;
                }
            } elseif ($field->getType() === NodeTypeField::DATETIME_T) {
                $type = CompareDatetimeType::class;
            } elseif ($field->getType() === NodeTypeField::DATE_T) {
                $type = CompareDateType::class;
            } else {
                $type = NodeSourceType::getFormTypeFromFieldType($field);
            }

            if ($field->getType() === NodeTypeField::MARKDOWN_T ||
                $field->getType() === NodeTypeField::STRING_T ||
                $field->getType() === NodeTypeField::TEXT_T ||
                $field->getType() === NodeTypeField::EMAIL_T ||
                $field->getType() === NodeTypeField::JSON_T ||
                $field->getType() === NodeTypeField::YAML_T ||
                $field->getType() === NodeTypeField::CSS_T
            ) {
                $builder->add(
                    $this->createTextSearchForm($builder, $field->getVarName(), $field->getLabel())
                );
            } else {
                $builder->add($field->getVarName(), $type, $option);
            }
        }
        return $builder;
    }
}
