<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Doctrine\Generators;

use RZ\Roadiz\Core\Entities\NodeType;

interface NodeTypeResolverInterface
{
    /**
     * @param string $nodeTypeName
     * @return NodeType
     */
    public function get(string $nodeTypeName);
}
