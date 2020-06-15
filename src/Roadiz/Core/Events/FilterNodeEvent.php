<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use RZ\Roadiz\Core\Entities\Node;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class FilterNodeEvent
 *
 * @package RZ\Roadiz\Core\Events
 */
abstract class FilterNodeEvent extends Event
{
    /**
     * @var Node
     */
    protected $node;

    /**
     * FilterNodeEvent constructor.
     *
     * @param Node $node
     */
    public function __construct(Node $node)
    {
        $this->node = $node;
    }

    /**
     * @return Node
     */
    public function getNode(): Node
    {
        return $this->node;
    }
}
