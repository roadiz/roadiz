<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Doctrine\Generators;

use RZ\Roadiz\Core\Entities\NodeTypeField;

/**
 * @package RZ\Roadiz\Utils\Doctrine\Generators
 */
class NodesFieldGenerator extends AbstractFieldGenerator
{
    /**
     * @var NodeTypeResolverInterface
     */
    private $nodeTypeResolver;

    /**
     * @param NodeTypeField $field
     * @param NodeTypeResolverInterface $nodeTypeResolver
     * @param array $options
     */
    public function __construct(NodeTypeField $field, NodeTypeResolverInterface $nodeTypeResolver, array $options = [])
    {
        parent::__construct($field, $options);
        $this->nodeTypeResolver = $nodeTypeResolver;
    }

    /**
     * @return string
     */
    protected function getFieldSourcesName(): string
    {
        return $this->field->getVarName().'Sources';
    }
    /**
     * @return bool
     */
    protected function hasOnlyOneNodeType()
    {
        if (null !== $this->field->getDefaultValues()) {
            return count(explode(',', $this->field->getDefaultValues() ?? '')) === 1;
        }
        return false;
    }

    /**
     * @return string
     */
    protected function getRepositoryClass(): string
    {
        if (null !== $this->field->getDefaultValues() && $this->hasOnlyOneNodeType() === true) {
            $nodeTypeName = trim(explode(',', $this->field->getDefaultValues() ?? '')[0]);

            $nodeType = $this->nodeTypeResolver->get($nodeTypeName);
            if (null !== $nodeType) {
                return $nodeType->getSourceEntityFullQualifiedClassName();
            }
        }
        return $this->options['parent_class'];
    }

    /**
     * @inheritDoc
     */
    public function getFieldGetter(): string
    {
        return '
    /**
     * @return \\'.$this->options['node_class'].'[] '.$this->field->getVarName().' array
     * @deprecated Use '.$this->field->getGetterName().'Sources() instead to directly handle node-sources
     * @Serializer\Exclude
     */
    public function '.$this->field->getGetterName().'()
    {
        trigger_error(
            \'Method \' . __METHOD__ . \' is deprecated and will be removed in Roadiz v1.6. Use '.$this->field->getGetterName().'Sources instead to deal with NodesSources.\',
            E_USER_DEPRECATED
        );

        if (null === $this->' . $this->field->getVarName() . ') {
            if (null !== $this->objectManager) {
                 $this->' . $this->field->getVarName() . ' = $this->objectManager
                      ->getRepository(\\'.$this->options['node_class'].'::class)
                      ->findByNodeAndFieldAndTranslation(
                          $this->getNode(),
                          $this->getNode()->getNodeType()->getFieldByName("'.$this->field->getVarName().'"),
                          $this->getTranslation()
                      );
            } else {
                $this->' . $this->field->getVarName() . ' = [];
            }
        }
        return $this->' . $this->field->getVarName() . ';
    }
    /**
     * ' . $this->getFieldSourcesName() .' NodesSources direct field buffer.
     * (Virtual field, this var is a buffer)
     * @Serializer\Exclude
     * @var \\'.$this->getRepositoryClass().'[]|null
     */
    private $'.$this->getFieldSourcesName().';

    /**
     * @return \\'.$this->getRepositoryClass().'[] '.$this->field->getVarName().' nodes-sources array
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"nodes_sources", "nodes_sources_'.($this->field->getGroupNameCanonical() ?: 'default').'"})
     * @Serializer\SerializedName("'.$this->field->getVarName().'")
     */
    public function '.$this->field->getGetterName().'Sources()
    {
        if (null === $this->' . $this->getFieldSourcesName() . ') {
            if (null !== $this->objectManager) {
                 $this->' . $this->getFieldSourcesName() . ' = $this->objectManager
                      ->getRepository(\\'. $this->getRepositoryClass() .'::class)
                      ->findByNodesSourcesAndFieldAndTranslation(
                          $this,
                          $this->getNode()->getNodeType()->getFieldByName("'.$this->field->getName().'")
                      );
            } else {
                $this->' . $this->getFieldSourcesName() . ' = [];
            }
        }
        return $this->' . $this->getFieldSourcesName() . ';
    }'.PHP_EOL;
    }
}
