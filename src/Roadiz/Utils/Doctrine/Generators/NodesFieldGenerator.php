<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Doctrine\Generators;

use RZ\Roadiz\Core\Bags\NodeTypes;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;

/**
 * @package RZ\Roadiz\Utils\Doctrine\Generators
 */
class NodesFieldGenerator extends AbstractFieldGenerator
{
    /**
     * @var NodeTypes
     */
    private $nodeTypesBag;

    /**
     * NodesFieldGenerator constructor.
     *
     * @param NodeTypeField $field
     * @param NodeTypes     $nodeTypesBag
     */
    public function __construct(NodeTypeField $field, NodeTypes $nodeTypesBag, array $options = [])
    {
        parent::__construct($field, $options);
        $this->nodeTypesBag = $nodeTypesBag;
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

            /** @var NodeType $nodeType */
            $nodeType = $this->nodeTypesBag->get($nodeTypeName);
            if (null !== $nodeType) {
                return $nodeType->getSourceEntityFullQualifiedClassName();
            }
        }
        return NodesSources::class;
    }

    /**
     * @inheritDoc
     */
    public function getFieldGetter(): string
    {
        return '
    /**
     * @return Node[] '.$this->field->getVarName().' array
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
                      ->getRepository(Node::class)
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
     * @var NodesSources[]|null
     */
    private $'.$this->getFieldSourcesName().';

    /**
     * @return NodesSources[] '.$this->field->getVarName().' nodes-sources array
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
