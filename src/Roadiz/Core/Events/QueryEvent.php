<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use Doctrine\ORM\Query;
use Symfony\Contracts\EventDispatcher\Event;

class QueryEvent extends Event
{
    /**
     * @var Query
     */
    protected $query;

    /**
     * @var string
     */
    protected $entityClass;

    /**
     * @param Query  $query
     * @param string $entityClass
     */
    public function __construct(Query $query, string $entityClass)
    {
        $this->query = $query;
        $this->entityClass = $entityClass;
    }

    /**
     * @return Query
     */
    public function getQuery(): Query
    {
        return $this->query;
    }

    /**
     * @return string
     */
    public function getEntityClass(): string
    {
        return $this->entityClass;
    }
}
