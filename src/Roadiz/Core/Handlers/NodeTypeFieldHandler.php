<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Handlers;

use Doctrine\Persistence\ObjectManager;
use Pimple\Container;
use RZ\Roadiz\Core\Entities\NodeTypeField;

/**
 * Handle operations with node-type fields entities.
 */
class NodeTypeFieldHandler extends AbstractHandler
{
    private ?NodeTypeField $nodeTypeField = null;
    private Container $container;

    public function getNodeTypeField(): NodeTypeField
    {
        if (null === $this->nodeTypeField) {
            throw new \BadMethodCallException('NodeTypeField is null');
        }
        return $this->nodeTypeField;
    }

    /**
     * @param NodeTypeField $nodeTypeField
     * @return $this
     */
    public function setNodeTypeField(NodeTypeField $nodeTypeField)
    {
        $this->nodeTypeField = $nodeTypeField;
        return $this;
    }

    /**
     * Create a new node-type-field handler with node-type-field to handle.
     *
     * @param ObjectManager $objectManager
     * @param Container $container
     */
    public function __construct(ObjectManager $objectManager, Container $container)
    {
        parent::__construct($objectManager);
        $this->container = $container;
    }

    /**
     * Clean position for current node siblings.
     *
     * @param bool $setPosition
     * @return float Return the next position after the **last** node
     */
    public function cleanPositions(bool $setPosition = false): float
    {
        if ($this->nodeTypeField->getNodeType() !== null) {
            /** @var NodeTypeHandler $nodeTypeHandler */
            $nodeTypeHandler = $this->container['factory.handler']
                                    ->getHandler($this->nodeTypeField->getNodeType());
            return $nodeTypeHandler->cleanPositions();
        }

        return 1;
    }
}
