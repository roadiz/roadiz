<?php
/**
 * Created by PhpStorm.
 * User: adrien
 * Date: 28/03/2017
 * Time: 19:38
 */

namespace Themes\Rozier\Models;

use Pimple\Container;
use RZ\Roadiz\Core\Entities\NodeType;

class NodeTypeModel
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
