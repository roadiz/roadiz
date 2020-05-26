<?php
declare(strict_types=1);

namespace Themes\Rozier\Models;

use Pimple\Container;
use RZ\Roadiz\Core\Entities\NodeType;

/**
 * Class NodeTypeModel.
 *
 * @package Themes\Rozier\Models
 */
class NodeTypeModel implements ModelInterface
{
    public static $thumbnailArray;
    /**
     * @var NodeType
     */
    private $nodeType;
    /**
     * @var Container
     */
    private $container;

    /**
     * NodeModel constructor.
     * @param NodeType $nodeType
     * @param Container $container
     */
    public function __construct(NodeType $nodeType, Container $container)
    {
        $this->nodeType = $nodeType;
        $this->container = $container;
    }

    public function toArray()
    {
        $result = [
            'id' => $this->nodeType->getId(),
            'nodeName' => $this->nodeType->getName(),
            'name' => $this->nodeType->getDisplayName(),
            'color' => $this->nodeType->getColor(),
        ];

        return $result;
    }
}
