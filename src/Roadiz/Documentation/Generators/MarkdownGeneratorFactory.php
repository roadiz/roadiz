<?php
declare(strict_types=1);

namespace RZ\Roadiz\Documentation\Generators;

use RZ\Roadiz\Core\Bags\NodeTypes;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use Symfony\Component\Translation\Translator;

final class MarkdownGeneratorFactory
{
    /**
     * @var NodeTypes
     */
    private $nodeTypesBag;
    /**
     * @var Translator
     */
    private $translator;

    /**
     * FieldGeneratorFactory constructor.
     *
     * @param NodeTypes  $nodeTypesBag
     * @param Translator $translator
     */
    public function __construct(NodeTypes $nodeTypesBag, Translator $translator)
    {
        $this->nodeTypesBag = $nodeTypesBag;
        $this->translator = $translator;
    }

    public function getHumanBool(bool $bool): string
    {
        return $bool ? $this->translator->trans('docs.yes') : $this->translator->trans('docs.no');
    }

    /**
     * @param NodeType $nodeType
     *
     * @return NodeTypeGenerator
     */
    public function createForNodeType(NodeType $nodeType): NodeTypeGenerator
    {
        return new NodeTypeGenerator(
            $nodeType,
            $this->nodeTypesBag,
            $this->translator,
            $this
        );
    }

    /**
     * @param NodeTypeField $field
     *
     * @return AbstractFieldGenerator
     */
    public function createForNodeTypeField(NodeTypeField $field): AbstractFieldGenerator
    {
        switch ($field->getType()) {
            case NodeTypeField::NODES_T:
                return new NodeReferencesFieldGenerator($this, $field, $this->nodeTypesBag, $this->translator);
            case NodeTypeField::CHILDREN_T:
                return new ChildrenNodeFieldGenerator($this, $field, $this->nodeTypesBag, $this->translator);
            case NodeTypeField::MULTIPLE_T:
            case NodeTypeField::ENUM_T:
                return new DefaultValuedFieldGenerator($this, $field, $this->nodeTypesBag, $this->translator);
            default:
                return new CommonFieldGenerator($this, $field, $this->nodeTypesBag, $this->translator);
        }
    }
}
