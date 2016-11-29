<?php
/**
 * Copyright (c) 2016. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file NodeSourceType.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace RZ\Roadiz\CMS\Forms\NodeSource;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\CMS\Forms\CssType;
use RZ\Roadiz\CMS\Forms\CustomFormsNodesType;
use RZ\Roadiz\CMS\Forms\DocumentsType;
use RZ\Roadiz\CMS\Forms\EnumerationType;
use RZ\Roadiz\CMS\Forms\JsonType;
use RZ\Roadiz\CMS\Forms\MarkdownType;
use RZ\Roadiz\CMS\Forms\MultipleEnumerationType;
use RZ\Roadiz\CMS\Forms\NodesType;
use RZ\Roadiz\Core\Entities\CustomForm;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Type;
use Themes\Rozier\Forms\NodeTreeType;

class NodeSourceType extends AbstractType
{
    /**
     * @var NodeType
     */
    private $nodeType;

    public function __construct(NodeType $nodeType)
    {
        $this->nodeType = $nodeType;
    }

    /**
     * @param  FormBuilderInterface $builder
     * @param  array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $fields = $this->getFieldsForSource($builder->getData(), $options['entityManager']);

        $builder->add(
            'title',
            'text',
            [
                'label' => 'title',
                'required' => false,
                'attr' => [
                    'data-desc' => '',
                    'data-field-group' => 'default',
                    'data-dev-name' => '{{ nodeSource.' . StringHandler::camelCase('title') . ' }}',
                ],
            ]
        );
        /** @var NodeTypeField $field */
        foreach ($fields as $field) {
            $builder->add(
                $field->getName(),
                $this->getFormTypeFromFieldType($builder->getData(), $field, $options),
                $this->getFormOptionsFromFieldType($field)
            );
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => NodeType::getGeneratedEntitiesNamespace().'\\'.$this->nodeType->getSourceEntityClassName(),
            'property' => 'id'
        ]);
        $resolver->setRequired([
            'entityManager',
            'controller',
        ]);
        $resolver->setAllowedTypes('controller', 'RZ\Roadiz\CMS\Controllers\Controller');
        $resolver->setAllowedTypes('entityManager', 'Doctrine\ORM\EntityManager');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'source';
    }

    /**
     * @param NodesSources $source
     * @param EntityManager $entityManager
     * @return array|null
     */
    private function getFieldsForSource(NodesSources $source, EntityManager $entityManager)
    {
        $criteria = [
            'nodeType' => $this->nodeType,
            'visible' => true,
        ];
        $position = [
            'position' => 'ASC',
        ];
        if (!$source->getTranslation()->isDefaultTranslation()) {
            $criteria = array_merge($criteria, ['universal' => false]);
        }

        return $entityManager->getRepository('RZ\Roadiz\Core\Entities\NodeTypeField')
            ->findBy($criteria, $position);
    }

    /**
     * Returns a Symfony Form type according to a node-type field.
     *
     * @param NodesSources $nodeSource
     * @param NodeTypeField $field
     * @param array $options
     * @return AbstractType
     */
    public function getFormTypeFromFieldType(NodesSources $nodeSource, NodeTypeField $field, array $options)
    {
        switch ($field->getType()) {
            case NodeTypeField::DOCUMENTS_T:
                /** @var Document[] $documents */
                $documents = $nodeSource->getHandler()
                    ->getDocumentsFromFieldName($field->getName());

                return new DocumentsType($documents, $options['entityManager']);
            case NodeTypeField::NODES_T:
                /** @var Node[] $nodes */
                $nodes = $options['entityManager']->getRepository('RZ\Roadiz\Core\Entities\Node')
                    ->findByNodeAndFieldName(
                        $nodeSource->getNode(),
                        $field->getName()
                    );

                return new NodesType($nodes, $options['entityManager']);
            case NodeTypeField::CUSTOM_FORMS_T:
                /** @var CustomForm[] $customForms */
                $customForms = $nodeSource->getNode()->getHandler()
                    ->getCustomFormsFromFieldName($field->getName());

                return new CustomFormsNodesType($customForms, $options['entityManager']);
            case NodeTypeField::CHILDREN_T:
                /*
                 * NodeTreeType is a virtual type which is only available
                 * with Rozier backend theme.
                 */
                return new NodeTreeType(
                    $nodeSource,
                    $field,
                    $options['controller']
                );
            case NodeTypeField::JSON_T:
                return new JsonType();
            case NodeTypeField::CSS_T:
                return new CssType();
            case NodeTypeField::MARKDOWN_T:
                return new MarkdownType();
            case NodeTypeField::ENUM_T:
                return new EnumerationType($field);
            case NodeTypeField::MULTIPLE_T:
                return new MultipleEnumerationType($field);
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
        if ($field->isUniversal()) {
            $options['attr']['data-universal'] = true;
        }
        if ('' !== $field->getDescription()) {
            $options['attr']['data-desc'] = $field->getDescription();
        }
        if ($field->getMinLength() > 0) {
            $options['attr']['data-min-length'] = $field->getMinLength();
        }
        if ($field->getMaxLength() > 0) {
            $options['attr']['data-max-length'] = $field->getMaxLength();
        }
        if ($field->isVirtual()) {
            $options['mapped'] = false;
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
                        new Type('numeric'),
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
                        new Type('numeric'),
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
}
