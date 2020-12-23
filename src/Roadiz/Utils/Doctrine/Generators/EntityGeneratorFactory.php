<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Doctrine\Generators;

use RZ\Roadiz\Core\Entities\NodeType;

final class EntityGeneratorFactory
{
    /**
     * @var NodeTypeResolverInterface
     */
    private $nodeTypeResolverBag;
    /**
     * @var array
     */
    private $options;

    /**
     * @param NodeTypeResolverInterface $nodeTypeResolverBag
     * @param array $options
     */
    public function __construct(NodeTypeResolverInterface $nodeTypeResolverBag, array $options)
    {
        $this->nodeTypeResolverBag = $nodeTypeResolverBag;
        $this->options = $options;
    }

    public function create(NodeType $nodeType): EntityGenerator
    {
        return new EntityGenerator($nodeType, $this->nodeTypeResolverBag, $this->options);
    }
}
