<?php
declare(strict_types=1);

namespace RZ\Roadiz\Documentation\Generators;

use RZ\Roadiz\Core\Bags\NodeTypes;
use RZ\Roadiz\Core\Entities\NodeType;
use Symfony\Component\Translation\Translator;

class DocumentationGenerator
{
    /**
     * @var NodeTypes
     */
    private $nodeTypesBag;

    /**
     * @var array
     */
    private $reachableTypeGenerators;

    /**
     * @var array
     */
    private $nonReachableTypeGenerators;

    /**
     * @var Translator
     */
    private $translator;
    /**
     * @var MarkdownGeneratorFactory
     */
    private $markdownGeneratorFactory;

    /**
     * @param NodeTypes  $nodeTypesBag
     * @param Translator $translator
     */
    public function __construct(NodeTypes $nodeTypesBag, Translator $translator)
    {
        $this->nodeTypesBag = $nodeTypesBag;
        $this->translator = $translator;
        $this->markdownGeneratorFactory = new MarkdownGeneratorFactory($nodeTypesBag, $translator);
    }

    protected function getAllNodeTypes(): array
    {
        return array_unique($this->nodeTypesBag->all());
    }

    protected function getReachableTypes(): array
    {
        return array_filter($this->getAllNodeTypes(), function (NodeType $nodeType) {
            return $nodeType->isReachable();
        });
    }

    protected function getNonReachableTypes(): array
    {
        return array_filter($this->getAllNodeTypes(), function (NodeType $nodeType) {
            return !$nodeType->isReachable();
        });
    }

    /**
     * @return NodeTypeGenerator[]
     */
    public function getReachableTypeGenerators(): array
    {
        if (null === $this->reachableTypeGenerators) {
            $this->reachableTypeGenerators = array_map(function (NodeType $nodeType) {
                return $this->markdownGeneratorFactory->createForNodeType($nodeType);
            }, $this->getReachableTypes());
        }
        return $this->reachableTypeGenerators;
    }

    /**
     * @return NodeTypeGenerator[]
     */
    public function getNonReachableTypeGenerators(): array
    {
        if (null === $this->nonReachableTypeGenerators) {
            $this->nonReachableTypeGenerators = array_map(function (NodeType $nodeType) {
                return $this->markdownGeneratorFactory->createForNodeType($nodeType);
            }, $this->getNonReachableTypes());
        }
        return $this->nonReachableTypeGenerators;
    }

    public function getNavBar(): string
    {
        /*
         * <!-- _navbar.md -->

            * [Introduction](/)
            * Blocs
                * [Groupe de blocs](blocks/groupblock.md)
                * [Bloc de contenu](blocks/contentblock.md)
         */

        $pages = [];
        foreach ($this->getReachableTypeGenerators() as $reachableTypeGenerator) {
            $pages[] = $reachableTypeGenerator->getMenuEntry();
        }

        $blocks = [];
        foreach ($this->getNonReachableTypeGenerators() as $nonReachableTypeGenerator) {
            $blocks[] = $nonReachableTypeGenerator->getMenuEntry();
        }

        return implode("\n", [
            '* ' . $this->translator->trans('docs.pages'),
            "    * " . implode("\n    * ", $pages),
            '* ' . $this->translator->trans('docs.blocks'),
            "    * " . implode("\n    * ", $blocks)
        ]);
    }
}
