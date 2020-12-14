<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use RZ\Roadiz\Core\Entities\Tag;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @package RZ\Roadiz\Core\Events
 */
abstract class FilterTagEvent extends Event
{
    /**
     * @var Tag
     */
    protected $tag;

    /**
     * @param Tag $tag
     */
    public function __construct(Tag $tag)
    {
        $this->tag = $tag;
    }

    /**
     * @return Tag
     */
    public function getTag()
    {
        return $this->tag;
    }
}
