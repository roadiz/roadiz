<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Doctrine\Generators;

use RZ\Roadiz\Core\AbstractEntities\AbstractField;
use RZ\Roadiz\Core\Bags\NodeTypes;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Yaml\Yaml;

/**
 * @package RZ\Roadiz\Utils\Doctrine\Generators
 */
class EntityGenerator
{
    /**
     * @var NodeType
     */
    private $nodeType;

    /**
     * @var array
     */
    private $fieldGenerators;

    /**
     * @var NodeTypes
     */
    private $nodeTypesBag;

    /**
     * @var array
     */
    protected $options;

    /**
     * @param NodeType $nodeType
     * @param NodeTypes $nodeTypesBag
     * @param array $options
     */
    public function __construct(NodeType $nodeType, NodeTypes $nodeTypesBag, array $options = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->nodeType = $nodeType;
        $this->nodeTypesBag = $nodeTypesBag;
        $this->fieldGenerators = [];
        $this->options = $resolver->resolve($options);

        foreach ($this->nodeType->getFields() as $field) {
            $this->fieldGenerators[] = $this->getFieldGenerator($field);
        }
        $this->fieldGenerators = array_filter($this->fieldGenerators);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'use_native_json' => true,
        ]);
        $resolver->setRequired([
            'parent_class',
            'node_class',
            'translation_class',
            'document_class',
            'document_proxy_class',
            'custom_form_class',
            'custom_form_proxy_class',
            'repository_class',
            'namespace',
            'use_native_json'
        ]);
        $resolver->setAllowedTypes('parent_class', 'string');
        $resolver->setAllowedTypes('node_class', 'string');
        $resolver->setAllowedTypes('translation_class', 'string');
        $resolver->setAllowedTypes('document_class', 'string');
        $resolver->setAllowedTypes('document_proxy_class', 'string');
        $resolver->setAllowedTypes('custom_form_class', 'string');
        $resolver->setAllowedTypes('custom_form_proxy_class', 'string');
        $resolver->setAllowedTypes('repository_class', 'string');
        $resolver->setAllowedTypes('namespace', 'string');
        $resolver->setAllowedTypes('use_native_json', 'bool');
    }

    /**
     * @param NodeTypeField $field
     * @return AbstractFieldGenerator|null
     */
    protected function getFieldGenerator(NodeTypeField $field): ?AbstractFieldGenerator
    {
        if ($field->getType() === AbstractField::YAML_T) {
            return new YamlFieldGenerator($field, $this->options);
        }
        if ($field->getType() === AbstractField::COLLECTION_T) {
            return new CollectionFieldGenerator($field, $this->options);
        }
        if ($field->getType() === AbstractField::CUSTOM_FORMS_T) {
            return new CustomFormsFieldGenerator($field, $this->options);
        }
        if ($field->getType() === AbstractField::DOCUMENTS_T) {
            return new DocumentsFieldGenerator($field, $this->options);
        }
        if ($field->getType() === AbstractField::MANY_TO_ONE_T) {
            return new ManyToOneFieldGenerator($field, $this->options);
        }
        if ($field->getType() === AbstractField::MANY_TO_MANY_T) {
            $configuration = Yaml::parse($field->getDefaultValues() ?? '');
            if (isset($configuration['proxy']) && !empty($configuration['proxy']['classname'])) {
                /*
                 * Manually create a Many to Many relation using a proxy class
                 * for handling position for example.
                 */
                return new ProxiedManyToManyFieldGenerator($field, $this->options);
            }
            return new ManyToManyFieldGenerator($field, $this->options);
        }
        if ($field->getType() === AbstractField::NODES_T) {
            return new NodesFieldGenerator($field, $this->nodeTypesBag, $this->options);
        }
        if (!$field->isVirtual()) {
            return new NonVirtualFieldGenerator($field, $this->options);
        }

        return null;
    }

    /**
     * @return string
     */
    public function getClassContent(): string
    {
        return $this->getClassHeader().
            $this->getClassAnnotations().
            $this->getClassBody();
    }

    /**
     * @return string
     */
    protected function getClassBody(): string
    {
        return 'class '.$this->nodeType->getSourceEntityClassName().' extends \\'.$this->options['parent_class'].'
{
    ' . $this->getClassProperties() . $this->getClassConstructor() . $this->getNodeTypeNameGetter() . $this->getClassMethods() . '
}'.PHP_EOL;
    }

    /**
     * @return string
     */
    protected function getClassHeader(): string
    {
        /*
         * BE CAREFUL, USE statements are required for field generators which
         * are using ::class syntax!
         */
        return '<?php
declare(strict_types=1);
/*
 * THIS IS A GENERATED FILE, DO NOT EDIT IT
 * IT WILL BE RECREATED AT EACH NODE-TYPE UPDATE
 */
namespace '.$this->options['namespace'].';

use JMS\Serializer\Annotation as Serializer;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;'.PHP_EOL.PHP_EOL;
    }

    /**
     * @return string
     */
    protected function getClassAnnotations(): string
    {
        $indexes = [];
        /** @var AbstractFieldGenerator $fieldGenerator */
        foreach ($this->fieldGenerators as $fieldGenerator) {
            $indexes[] = $fieldGenerator->getFieldIndex();
        }
        $indexes = array_filter($indexes);
        return '
/**
 * DO NOT EDIT
 * Generated custom node-source type by Roadiz.
 *
 * @ORM\Entity(repositoryClass="\\'.$this->options['repository_class'].'")
 * @ORM\Table(name="'.$this->nodeType->getSourceEntityTableName().'", indexes={'.implode(',', $indexes).'})
 */'.PHP_EOL;
    }

    /**
     * @return string
     */
    protected function getClassProperties(): string
    {
        $fieldsArray = [];
        /** @var AbstractFieldGenerator $fieldGenerator */
        foreach ($this->fieldGenerators as $fieldGenerator) {
            $fieldsArray[] = $fieldGenerator->getField();
        }
        $fieldsArray = array_filter($fieldsArray);

        return implode('', $fieldsArray);
    }

    /**
     * @return string
     */
    protected function getClassConstructor(): string
    {
        $constructorArray = [];
        /** @var AbstractFieldGenerator $fieldGenerator */
        foreach ($this->fieldGenerators as $fieldGenerator) {
            $constructorArray[] = $fieldGenerator->getFieldConstructorInitialization();
        }
        $constructorArray = array_filter($constructorArray);

        if (count($constructorArray) > 0) {
            return '
    public function __construct(\\'.$this->options['node_class'].' $node, \\'.$this->options['translation_class'].' $translation)
    {
        parent::__construct($node, $translation);

        '.implode(PHP_EOL, $constructorArray).'
    }'.PHP_EOL;
        }

        return '';
    }

    /**
     * @return string
     */
    protected function getNodeTypeNameGetter(): string
    {
            return '
    /**
     * @return string
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"nodes_sources", "nodes_sources_default"})
     * @Serializer\SerializedName("@type")
     */
    public function getNodeTypeName(): string
    {
        return \''.$this->nodeType->getName().'\';
    }'.PHP_EOL;
    }

    /**
     * @return string
     */
    protected function getClassMethods(): string
    {
        return '
    public function __toString()
    {
        return \'['.$this->nodeType->getSourceEntityClassName().'] \' . parent::__toString();
    }';
    }
}
