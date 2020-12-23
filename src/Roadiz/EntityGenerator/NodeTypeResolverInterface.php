<?php
declare(strict_types=1);

namespace RZ\Roadiz\EntityGenerator;

use RZ\Roadiz\Contracts\NodeType\NodeTypeInterface;

interface NodeTypeResolverInterface
{
    /**
     * @param string $nodeTypeName
     * @return NodeTypeInterface|null
     */
    public function get(string $nodeTypeName);
}
