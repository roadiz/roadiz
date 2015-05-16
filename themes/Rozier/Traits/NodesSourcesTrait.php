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
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\Translation\Translator;
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

            /*
             * update node name if dynamic option enabled and
             * default translation
             */
            if (true === $nodeSource->getNode()->isDynamicNodeName() &&
                $nodeSource->getTranslation()->isDefaultTranslation()) {
                $testingNodeName = StringHandler::slugify($data['title']);

                /*
                 * node name wont be updated if name already taken
                 */
                if ($testingNodeName != $nodeSource->getNode()->getNodeName() &&
                    false === (boolean) $this->getService('em')->getRepository('RZ\Roadiz\Core\Entities\UrlAlias')->exists($testingNodeName) &&
                    false === (boolean) $this->getService('em')->getRepository('RZ\Roadiz\Core\Entities\Node')->exists($testingNodeName)) {
                    $nodeSource->getNode()->setNodeName($data['title']);
                }
            }
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
                              ->createNamedBuilder('source', 'form', $sourceDefaults)
                              ->add(
                                  'title',
                                  'text',
                                  [
                                      'label' => $this->getTranslator()->trans('title'),
                                      'required' => false,
                                      'attr' => [
                                          'data-desc' => '',
                                          'data-dev-name' => '{{ nodeSource.' . StringHandler::camelCase('title') . ' }}',
                                      ],
                                  ]
                              );
        foreach ($fields as $field) {
            $sourceBuilder->add(
                $field->getName(),
                $this->getFormTypeFromFieldType($source, $field, $this),
                $this->getFormOptionsFromFieldType($source, $field)
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

                return new \RZ\Roadiz\CMS\Forms\DocumentsType($documents);
            case NodeTypeField::NODES_T:
                $nodes = $nodeSource->getNode()->getHandler()
                                    ->getNodesFromFieldName($field->getName());

                return new \RZ\Roadiz\CMS\Forms\NodesType($nodes);
            case NodeTypeField::CUSTOM_FORMS_T:
                $customForms = $nodeSource->getNode()->getHandler()
                                          ->getCustomFormsFromFieldName($field->getName());

                return new \RZ\Roadiz\CMS\Forms\CustomFormsNodesType($customForms);
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
     * Returns an option array for creating a Symfony Form
     * according to a node-type field.
     *
     * @param  NodesSources  $nodeSource
     * @param  NodeTypeField $field
     *
     * @return array
     */
    public function getFormOptionsFromFieldType(
        NodesSources $nodeSource,
        NodeTypeField $field
    ) {
        $label = $field->getLabel();
        $devName = '{{ nodeSource.' . StringHandler::camelCase($field->getName()) . ' }}';

        switch ($field->getType()) {
            case NodeTypeField::ENUM_T:
                return [
                    'label' => $label,
                    'empty_value' => 'choose.value',
                    'required' => false,
                    'attr' => [
                        'data-desc' => $field->getDescription(),
                        'data-dev-name' => $devName,
                    ],
                ];
            case NodeTypeField::DATETIME_T:
                return [
                    'date_widget' => 'single_text',
                    'date_format' => 'yyyy-MM-dd',
                    'label' => $label,
                    'required' => false,
                    'attr' => [
                        'data-desc' => $field->getDescription(),
                        'data-dev-name' => $devName,
                        'class' => 'rz-datetime-field',
                    ],
                    'empty_value' => [
                        'hour' => 'hour',
                        'minute' => 'minute',
                    ],
                ];
            case NodeTypeField::DATE_T:
                return [
                    'widget' => 'single_text',
                    'format' => 'yyyy-MM-dd',
                    'label' => $label,
                    'required' => false,
                    'attr' => [
                        'data-desc' => $field->getDescription(),
                        'data-dev-name' => $devName,
                        'class' => 'rz-date-field',
                    ],
                    'empty_value' => '',
                ];
            case NodeTypeField::INTEGER_T:
                return [
                    'label' => $label,
                    'required' => false,
                    'constraints' => [
                        new Type('integer'),
                    ],
                    'attr' => [
                        'data-desc' => $field->getDescription(),
                        'data-dev-name' => $devName,
                    ],
                ];
            case NodeTypeField::EMAIL_T:
                return [
                    'label' => $label,
                    'required' => false,
                    'constraints' => [
                        new \Symfony\Component\Validator\Constraints\Email(),
                    ],
                    'attr' => [
                        'data-desc' => $field->getDescription(),
                        'data-dev-name' => $devName,
                    ],
                ];
            case NodeTypeField::DECIMAL_T:
                return [
                    'label' => $label,
                    'required' => false,
                    'constraints' => [
                        new Type('double'),
                    ],
                    'attr' => [
                        'data-desc' => $field->getDescription(),
                        'data-dev-name' => $devName,
                    ],
                ];
            case NodeTypeField::COLOUR_T:
                return [
                    'label' => $label,
                    'required' => false,
                    'attr' => [
                        'data-desc' => $field->getDescription(),
                        'data-dev-name' => $devName,
                        'class' => 'colorpicker-input',
                    ],
                ];
            case NodeTypeField::GEOTAG_T:
                return [
                    'label' => $label,
                    'required' => false,
                    'attr' => [
                        'data-desc' => $field->getDescription(),
                        'data-dev-name' => $devName,
                        'class' => 'rz-geotag-field',
                    ],
                ];
            case NodeTypeField::MARKDOWN_T:
                return [
                    'label' => $label,
                    'required' => false,
                    'attr' => [
                        'class' => 'markdown_textarea',
                        'data-desc' => $field->getDescription(),
                        'data-min-length' => $field->getMinLength(),
                        'data-max-length' => $field->getMaxLength(),
                        'data-dev-name' => $devName,
                    ],
                ];
            default:
                return [
                    'label' => $label,
                    'required' => false,
                    'attr' => [
                        'data-desc' => $field->getDescription(),
                        'data-min-length' => $field->getMinLength(),
                        'data-max-length' => $field->getMaxLength(),
                        'data-dev-name' => $devName,
                    ],
                ];
        }
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
                        $tempDoc = Kernel::getService('em')
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
                        $tempCForm = Kernel::getService('em')
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
                        $tempNode = Kernel::getService('em')
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
