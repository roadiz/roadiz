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
 * @file NodesSourcesTrait.php
 * @author Maxime Constantinian
 */

namespace Themes\Rozier\Traits;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Events\FilterNodeEvent;
use RZ\Roadiz\Core\Events\NodeEvents;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Type;

trait NodesSourcesTrait
{
    /**
     * Edit node source parameters.
     *
     * @param array                               $data
     * @param RZ\Roadiz\Core\Entities\NodesSources $nodeSource
     *
     * @return void
     */
    private function editNodeSource($data, NodesSources $nodeSource)
    {
        if (isset($data['title'])) {
            $nodeSource->setTitle($data['title']);
        } else {
            // empty title
            $nodeSource->setTitle("");
        }

        $fields = $nodeSource->getNode()->getNodeType()->getFields();
        foreach ($fields as $field) {
            if (isset($data[$field->getName()])) {
                $this->setValueFromFieldType($data[$field->getName()], $nodeSource, $field);
            } else {
                $this->setValueFromFieldType(null, $nodeSource, $field);
            }
        }

        $this->getService('em')->flush();
    }

    /**
     * Update nodeName when title is available.
     *
     * @param  string       $title
     * @param  NodesSources $nodeSource
     */
    protected function updateNodeName(NodesSources $nodeSource)
    {
        $title = $nodeSource->getTitle();

        /*
         * update node name if dynamic option enabled and
         * default translation
         */
        if ("" != $title &&
            true === $nodeSource->getNode()->isDynamicNodeName() &&
            $nodeSource->getTranslation()->isDefaultTranslation()) {
            $testingNodeName = StringHandler::slugify($title);

            /*
             * node name wont be updated if name already taken
             */
            if ($testingNodeName != $nodeSource->getNode()->getNodeName() &&
                false === (boolean) $this->getService('em')->getRepository('RZ\Roadiz\Core\Entities\UrlAlias')->exists($testingNodeName) &&
                false === (boolean) $this->getService('em')->getRepository('RZ\Roadiz\Core\Entities\Node')->exists($testingNodeName)) {
                $nodeSource->getNode()->setNodeName($title);

                $this->getService('em')->flush();

                /*
                 * Dispatch event
                 */
                $event = new FilterNodeEvent($nodeSource->getNode());
                $this->getService('dispatcher')->dispatch(NodeEvents::NODE_UPDATED, $event);
            }
        }
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Node         $node
     * @param RZ\Roadiz\Core\Entities\NodesSources $source
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildEditSourceForm(Node $node, NodesSources $source)
    {
        $fields = $node->getNodeType()->getFields();
        /*
         * Create source default values
         */
        $sourceDefaults = [
            'title' => $source->getTitle(),
        ];
        foreach ($fields as $field) {
            if (!$field->isVirtual()) {
                $getter = $field->getGetterName();

                if (method_exists($source, $getter)) {
                    $sourceDefaults[$field->getName()] = $source->$getter();
                } else {
                    throw new \Exception($getter . ' method does not exist in ' . $node->getNodeType()->getName());
                }
            }
        }

        /*
         * Create subform for source
         */
        $sourceBuilder = $this->getService('formFactory')
                              ->createNamedBuilder('source', 'form', $sourceDefaults);
        /*
         * Add title and default fields
         */
        $sourceBuilder->add(
            'title',
            'text',
            [
                'label' => $this->getTranslator()->trans('title'),
                'required' => false,
                'attr' => [
                    'data-desc' => '',
                    'data-field-group' => 'default',
                    'data-dev-name' => '{{ nodeSource.' . StringHandler::camelCase('title') . ' }}',
                ],
            ]
        );
        foreach ($fields as $field) {
            $sourceBuilder->add(
                $field->getName(),
                $this->getFormTypeFromFieldType($source, $field, $this),
                $this->getFormOptionsFromFieldType($field)
            );
        }

        return $sourceBuilder->getForm();
    }

    /**
     * Returns a Symfony Form type according to a node-type field.
     *
     * @param mixed         $nodeSource
     * @param NodeTypeField $field
     * @param AppController $controller
     *
     * @return AbstractType
     */
    public function getFormTypeFromFieldType(NodesSources $nodeSource, NodeTypeField $field, $controller)
    {
        switch ($field->getType()) {
            case NodeTypeField::DOCUMENTS_T:
                $documents = $nodeSource->getHandler()
                                        ->getDocumentsFromFieldName($field->getName());

                return new \RZ\Roadiz\CMS\Forms\DocumentsType($documents, $this->getService('em'));
            case NodeTypeField::NODES_T:
                $nodes = $nodeSource->getNode()->getHandler()
                                    ->getNodesFromFieldName($field->getName());

                return new \RZ\Roadiz\CMS\Forms\NodesType($nodes, $this->getService('em'));
            case NodeTypeField::CUSTOM_FORMS_T:
                $customForms = $nodeSource->getNode()->getHandler()
                                          ->getCustomFormsFromFieldName($field->getName());

                return new \RZ\Roadiz\CMS\Forms\CustomFormsNodesType($customForms, $this->getService('em'));
            case NodeTypeField::CHILDREN_T:
                /*
             * NodeTreeType is a virtual type which is only available
             * with Rozier backend theme.
             */
                return new \Themes\Rozier\Forms\NodeTreeType(
                    $nodeSource,
                    $field,
                    $controller
                );
            case NodeTypeField::JSON_T:
                return new \RZ\Roadiz\CMS\Forms\JsonType();
            case NodeTypeField::CSS_T:
                return new \RZ\Roadiz\CMS\Forms\CssType();
            case NodeTypeField::MARKDOWN_T:
                return new \RZ\Roadiz\CMS\Forms\MarkdownType();
            case NodeTypeField::ENUM_T:
                return new \RZ\Roadiz\CMS\Forms\EnumerationType($field);
            case NodeTypeField::MULTIPLE_T:
                return new \RZ\Roadiz\CMS\Forms\MultipleEnumerationType($field);

            default:
                return NodeTypeField::$typeToForm[$field->getType()];
        }
    }

    /**
     * Get common options for your node-type field form components.
     *
     * @param  NodeTypeField $field
     *
     * @return array
     */
    public function getDefaultOptions(NodeTypeField $field)
    {
        $label = $field->getLabel();
        $devName = '{{ nodeSource.' . StringHandler::camelCase($field->getName()) . ' }}';
        $options = [
            'label' => $label,
            'required' => false,
            'attr' => [
                'data-field-group' => (null !== $field->getGroupName() && '' != $field->getGroupName()) ? $field->getGroupName() : 'default',
                'data-dev-name' => $devName,
                'autocomplete' => 'off',
            ],
        ];
        if ('' !== $field->getDescription()) {
            $options['attr']['data-desc'] = $field->getDescription();
        }
        if ($field->getMinLength() > 0) {
            $options['attr']['data-min-length'] = $field->getMinLength();
        }
        if ($field->getMaxLength() > 0) {
            $options['attr']['data-max-length'] = $field->getMaxLength();
        }

        return $options;
    }
    /**
     * Returns an option array for creating a Symfony Form
     * according to a node-type field.
     *
     * @param  NodeTypeField $field
     *
     * @return array
     */
    public function getFormOptionsFromFieldType(NodeTypeField $field)
    {
        $options = $this->getDefaultOptions($field);

        switch ($field->getType()) {
            case NodeTypeField::NODES_T:
                $options = array_merge_recursive($options, [
                    'attr' => [
                        'data-nodetypes' => json_encode(explode(',', $field->getDefaultValues()))
                    ],
                ]);
                break;
            case NodeTypeField::ENUM_T:
                $options = array_merge_recursive($options, [
                    'placeholder' => 'choose.value',
                ]);
                break;
            case NodeTypeField::DATETIME_T:
                $options = array_merge_recursive($options, [
                    'date_widget' => 'single_text',
                    'date_format' => 'yyyy-MM-dd',
                    'attr' => [
                        'class' => 'rz-datetime-field',
                    ],
                    'placeholder' => [
                        'hour' => 'hour',
                        'minute' => 'minute',
                    ],
                ]);
                break;
            case NodeTypeField::DATE_T:
                $options = array_merge_recursive($options, [
                    'widget' => 'single_text',
                    'format' => 'yyyy-MM-dd',
                    'attr' => [
                        'class' => 'rz-date-field',
                    ],
                    'placeholder' => '',
                ]);
                break;
            case NodeTypeField::INTEGER_T:
                $options = array_merge_recursive($options, [
                    'constraints' => [
                        new Type('integer'),
                    ],
                ]);
                break;
            case NodeTypeField::EMAIL_T:
                $options = array_merge_recursive($options, [
                    'constraints' => [
                        new Email(),
                    ],
                ]);
                break;
            case NodeTypeField::DECIMAL_T:
                $options = array_merge_recursive($options, [
                    'constraints' => [
                        new Type('double'),
                    ],
                ]);
                break;
            case NodeTypeField::COLOUR_T:
                $options = array_merge_recursive($options, [
                    'attr' => [
                        'class' => 'colorpicker-input',
                    ],
                ]);
                break;
            case NodeTypeField::GEOTAG_T:
                $options = array_merge_recursive($options, [
                    'attr' => [
                        'class' => 'rz-geotag-field',
                    ],
                ]);
                break;
            case NodeTypeField::MULTI_GEOTAG_T:
                $options = array_merge_recursive($options, [
                    'attr' => [
                        'class' => 'rz-multi-geotag-field',
                    ],
                ]);
                break;
            case NodeTypeField::MARKDOWN_T:
                $options = array_merge_recursive($options, [
                    'attr' => [
                        'class' => 'markdown_textarea',
                    ],
                ]);
                break;
        }

        return $options;
    }

    /**
     * Fill node-source content according to field type.
     *
     * @param mixed         $dataValue
     * @param NodesSources  $nodeSource
     * @param NodeTypeField $field
     *
     * @return void
     */
    public function setValueFromFieldType($dataValue, NodesSources $nodeSource, NodeTypeField $field)
    {
        switch ($field->getType()) {
            case NodeTypeField::DOCUMENTS_T:
                $hdlr = $nodeSource->getHandler();
                $hdlr->cleanDocumentsFromField($field);
                if (is_array($dataValue)) {
                    foreach ($dataValue as $documentId) {
                        $tempDoc = $this->getService('em')
                            ->find('RZ\Roadiz\Core\Entities\Document', (int) $documentId);
                        if ($tempDoc !== null) {
                            $hdlr->addDocumentForField($tempDoc, $field);
                        }
                    }
                }
                break;
            case NodeTypeField::CUSTOM_FORMS_T:
                $hdlr = $nodeSource->getNode()->getHandler();
                $hdlr->cleanCustomFormsFromField($field);
                if (is_array($dataValue)) {
                    foreach ($dataValue as $customFormId) {
                        $tempCForm = $this->getService('em')
                            ->find('RZ\Roadiz\Core\Entities\CustomForm', (int) $customFormId);
                        if ($tempCForm !== null) {
                            $hdlr->addCustomFormForField($tempCForm, $field);
                        }
                    }
                }
                break;
            case NodeTypeField::NODES_T:
                $hdlr = $nodeSource->getNode()->getHandler();
                $hdlr->cleanNodesFromField($field);

                if (is_array($dataValue)) {
                    foreach ($dataValue as $nodeId) {
                        $tempNode = $this->getService('em')
                            ->find('RZ\Roadiz\Core\Entities\Node', (int) $nodeId);
                        if ($tempNode !== null) {
                            $hdlr->addNodeForField($tempNode, $field);
                        }
                    }
                }
                break;
            case NodeTypeField::CHILDREN_T:
                break;
            default:
                $setter = $field->getSetterName();
                $nodeSource->$setter($dataValue);

                break;
        }
    }
}
