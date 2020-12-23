<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Doctrine\Generators;

use RZ\Roadiz\Core\Bags\NodeTypes;
use RZ\Roadiz\Core\Entities\NodeType;

final class EntityGeneratorFactory
{
    /**
     * @var NodeTypes
     */
    private $nodeTypesBag;
    /**
     * @var array
     */
    private $options;

    /**
     * @param NodeTypes $nodeTypesBag
     * @param array $options
     */
    public function __construct(NodeTypes $nodeTypesBag, array $options)
    {
        $this->nodeTypesBag = $nodeTypesBag;
        $this->options = $options;
    }

    public function create(NodeType $nodeType): EntityGenerator
    {
        return new EntityGenerator($nodeType, $this->nodeTypesBag, $this->options);
    }
}
