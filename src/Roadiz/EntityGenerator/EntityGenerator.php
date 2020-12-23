<?php
declare(strict_types=1);

namespace RZ\Roadiz\EntityGenerator;

use RZ\Roadiz\Contracts\NodeType\NodeTypeFieldInterface;
use RZ\Roadiz\Contracts\NodeType\NodeTypeInterface;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\EntityGenerator\Field\AbstractFieldGenerator;
use RZ\Roadiz\EntityGenerator\Field\CollectionFieldGenerator;
use RZ\Roadiz\EntityGenerator\Field\CustomFormsFieldGenerator;
use RZ\Roadiz\EntityGenerator\Field\DocumentsFieldGenerator;
use RZ\Roadiz\EntityGenerator\Field\ManyToManyFieldGenerator;
use RZ\Roadiz\EntityGenerator\Field\ManyToOneFieldGenerator;
use RZ\Roadiz\EntityGenerator\Field\NodesFieldGenerator;
use RZ\Roadiz\EntityGenerator\Field\NonVirtualFieldGenerator;
use RZ\Roadiz\EntityGenerator\Field\ProxiedManyToManyFieldGenerator;
use RZ\Roadiz\EntityGenerator\Field\YamlFieldGenerator;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Yaml\Yaml;

class EntityGenerator
{
    /**
     * @var NodeTypeInterface
     */
    private $nodeType;

    /**
     * @var array
     */
    private $fieldGenerators;

    /**
     * @var NodeTypeResolverInterface
     */
    private $nodeTypeResolver;

    /**
     * @var array
     */
    protected $options;

    /**
     * @param NodeTypeInterface $nodeType
     * @param NodeTypeResolverInterface $nodeTypeResolver
     * @param array $options
     */
    public function __construct(NodeTypeInterface $nodeType, NodeTypeResolverInterface $nodeTypeResolver, array $options = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->nodeType = $nodeType;
        $this->nodeTypeResolver = $nodeTypeResolver;
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
     * @param NodeTypeFieldInterface $field
     * @return AbstractFieldGenerator|null
     */
    protected function getFieldGenerator(NodeTypeFieldInterface $field): ?AbstractFieldGenerator
    {
        if ($field->isYaml()) {
            return new YamlFieldGenerator($field, $this->options);
        }
        if ($field->isCollection()) {
            return new CollectionFieldGenerator($field, $this->options);
        }
        if ($field->isCustomForms()) {
            return new CustomFormsFieldGenerator($field, $this->options);
        }
        if ($field->isDocuments()) {
            return new DocumentsFieldGenerator($field, $this->options);
        }
        if ($field->isManyToOne()) {
            return new ManyToOneFieldGenerator($field, $this->options);
        }
        if ($field->isManyToMany()) {
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
        if ($field->isNodes()) {
            return new NodesFieldGenerator($field, $this->nodeTypeResolver, $this->options);
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
    ' . $this->getClassProperties() .
        $this->getClassConstructor() .
        $this->getNodeTypeNameGetter() .
        $this->getClassMethods() . '
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
