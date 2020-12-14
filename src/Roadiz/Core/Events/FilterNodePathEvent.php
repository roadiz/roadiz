<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use RZ\Roadiz\Core\Entities\Node;

/**
 * @package RZ\Roadiz\Core\Events
 */
abstract class FilterNodePathEvent extends FilterNodeEvent
{
    /**
     * @var array
     */
    protected $paths;
    /**
     * @var \DateTime
     */
    protected $updatedAt;

    /**
     * @param Node           $node
     * @param array          $paths
     * @param \DateTime|null $updatedAt
     */
    public function __construct(Node $node, array $paths = [], \DateTime $updatedAt = null)
    {
        parent::__construct($node);
        $this->paths = $paths;
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return array
     */
    public function getPaths(): array
    {
        return $this->paths;
    }

    /**
     * @return \DateTime|null
     */
    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }
}
