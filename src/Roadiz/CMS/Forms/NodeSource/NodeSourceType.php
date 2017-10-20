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
use Pimple\Container;
use RZ\Roadiz\CMS\Controllers\Controller;
use RZ\Roadiz\CMS\Forms\CssType;
use RZ\Roadiz\CMS\Forms\EnumerationType;
use RZ\Roadiz\CMS\Forms\JsonType;
use RZ\Roadiz\CMS\Forms\MarkdownType;
use RZ\Roadiz\CMS\Forms\MultipleEnumerationType;
use RZ\Roadiz\CMS\Forms\YamlType;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Entities\Translation;
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
     * @param  FormBuilderInterface $builder
     * @param  array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $fields = $this->getFieldsForSource($builder->getData(), $options['entityManager'], $options['nodeType']);

        if ($options['withTitle'] === true) {
            $builder->add('base', NodeSourceBaseType::class, [
                'publishable' => $options['nodeType']->isPublishable(),
            ]);
        }
        /** @var NodeTypeField $field */
        foreach ($fields as $field) {
            if ($options['withVirtual'] === true || !$field->isVirtual()) {
                $builder->add(
                    $field->getName(),
                    $this->getFormTypeFromFieldType($builder->getData(), $field, $options),
                    $this->getFormOptionsFromFieldType($field)
                );
            }
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'property' => 'id',
            'withTitle' => true,
            'withVirtual' => true,
        ]);
        $resolver->setRequired([
            'class',
            'entityManager',
            'controller',
            'container',
            'nodeType',
        ]);
        $resolver->setAllowedTypes('container', Container::class);
        $resolver->setAllowedTypes('controller', Controller::class);
        $resolver->setAllowedTypes('entityManager', EntityManager::class);
        $resolver->setAllowedTypes('withTitle', 'boolean');
        $resolver->setAllowedTypes('withVirtual', 'boolean');
        $resolver->setAllowedTypes('nodeType', NodeType::class);
        $resolver->setAllowedTypes('class', 'string');
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
     * @param NodeType $nodeType
     * @return array|null
     */
    private function getFieldsForSource(NodesSources $source, EntityManager $entityManager, NodeType $nodeType)
    {
        $criteria = [
            'nodeType' => $nodeType,
            'visible' => true,
        ];

        $position = [
            'position' => 'ASC',
        ];

        if (!$this->needsUniversalFields($source, $entityManager)) {
            $criteria = array_merge($criteria, ['universal' => false]);
        }

        return $entityManager->getRepository(NodeTypeField::class)->findBy($criteria, $position);
    }

    /**
     * @param NodesSources $source
     * @param EntityManager $entityManager
     * @return bool
     */
    private function needsUniversalFields(NodesSources $source, EntityManager $entityManager)
    {
        return ($source->getTranslation()->isDefaultTranslation() || !$this->hasDefaultTranslation($source, $entityManager));
    }

    /**
     * @param NodesSources $source
     * @param EntityManager $entityManager
     * @return bool
     */
    private function hasDefaultTranslation(NodesSources $source, EntityManager $entityManager)
    {
        /** @var Translation $defaultTranslation */
        $defaultTranslation = $entityManager->getRepository(Translation::class)
                                            ->findDefault();

        $sourceCount = $entityManager->getRepository(NodesSources::class)
                                     ->setDisplayingAllNodesStatuses(true)
                                     ->setDisplayingNotPublishedNodes(true)
                                     ->countBy([
                                         'node' => $source->getNode(),
                                         'translation' => $defaultTranslation,
                                     ]);

        return $sourceCount === 1;
    }

    /**
     * Returns a Symfony Form type according to a node-type field.
     *
     * @param NodesSources $nodeSource
     * @param NodeTypeField $field
     * @param array $options
     * @return AbstractType|string
     */
    public function getFormTypeFromFieldType(NodesSources $nodeSource, NodeTypeField $field, array $options)
    {
        switch ($field->getType()) {
            case NodeTypeField::MULTI_PROVIDER_T:
            case NodeTypeField::SINGLE_PROVIDER_T:
                return new NodeSourceProviderType($nodeSource, $field, $options['entityManager'], $options['container']);
            case NodeTypeField::MANY_TO_ONE_T:
            case NodeTypeField::MANY_TO_MANY_T:
                return new NodeSourceJoinType($nodeSource, $field, $options['entityManager']);
            case NodeTypeField::DOCUMENTS_T:
                return new NodeSourceDocumentType(
                    $nodeSource,
                    $field,
                    $options['entityManager'],
                    $options['container']->offsetGet('nodes_sources.handler')
                );
            case NodeTypeField::NODES_T:
                return new NodeSourceNodeType(
                    $nodeSource,
                    $field,
                    $options['entityManager'],
                    $options['container']->offsetGet('node.handler')
                );
            case NodeTypeField::CUSTOM_FORMS_T:
                return new NodeSourceCustomFormType(
                    $nodeSource,
                    $field,
                    $options['entityManager'],
                    $options['container']->offsetGet('node.handler')
                );
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
                return JsonType::class;
            case NodeTypeField::CSS_T:
                return CssType::class;
            case NodeTypeField::YAML_T:
                return YamlType::class;
            case NodeTypeField::MARKDOWN_T:
                return MarkdownType::class;
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
        if ('' !== $field->getPlaceholder()) {
            $options['attr']['placeholder'] = $field->getPlaceholder();
        }
        if ($field->getMinLength() > 0) {
            $options['attr']['data-min-length'] = $field->getMinLength();
        }
        if ($field->getMaxLength() > 0) {
            $options['attr']['data-max-length'] = $field->getMaxLength();
        }
        if ($field->isVirtual() &&
            $field->getType() !== NodeTypeField::MANY_TO_ONE_T &&
            $field->getType() !== NodeTypeField::MANY_TO_MANY_T) {
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
            case NodeTypeField::MANY_TO_ONE_T:
            case NodeTypeField::MANY_TO_MANY_T:
                $options = array_merge_recursive($options, [
                    'attr' => [
                        'data-nodetypefield' => $field->getId(),
                    ],
                ]);
                break;
            case NodeTypeField::NODES_T:
                $options = array_merge_recursive($options, [
                    'attr' => [
                        'data-nodetypes' => json_encode(explode(',', $field->getDefaultValues()))
                    ],
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
            case NodeTypeField::COUNTRY_T:
                $options = array_merge_recursive($options, [
                    'expanded' => $field->isExpanded(),
                ]);
                if ('' !== $field->getPlaceholder()) {
                    $options['placeholder'] = $field->getPlaceholder();
                }
                if ($field->getDefaultValues() !== '') {
                    $countries = explode(',', $field->getDefaultValues());
                    $countries = array_map('trim', $countries);
                    $options = array_merge_recursive($options, [
                        'preferred_choices' => $countries,
                    ]);
                }
                break;
        }

        return $options;
    }
}
