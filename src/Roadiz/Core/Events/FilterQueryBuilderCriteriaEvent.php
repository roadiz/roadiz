<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use Doctrine\ORM\QueryBuilder;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @package RZ\Roadiz\Core\Events
 */
abstract class FilterQueryBuilderCriteriaEvent extends Event
{
    /**
     * @var string
     */
    protected $property;
    /**
     * @var mixed
     */
    protected $value;
    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;
    /**
     * @var string
     */
    protected $entityClass;
    /**
     * @var string
     */
    protected $actualEntityName;

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $entityClass
     * @param string $property
     * @param mixed $value
     * @param string $actualEntityName
     */
    public function __construct(QueryBuilder $queryBuilder, $entityClass, $property, $value, $actualEntityName)
    {
        $this->queryBuilder = $queryBuilder;
        $this->entityClass = $entityClass;
        $this->property = $property;
        $this->value = $value;
        $this->actualEntityName = $actualEntityName;
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return FilterQueryBuilderCriteriaEvent
     */
    public function setQueryBuilder(QueryBuilder $queryBuilder): self
    {
        $this->queryBuilder = $queryBuilder;
        return $this;
    }

    /**
     * @return string
     */
    public function getProperty()
    {
        return $this->property;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return bool
     */
    public function supports(): bool
    {
        return $this->entityClass === $this->actualEntityName;
    }

    /**
     * @return string
     */
    public function getActualEntityName()
    {
        return $this->actualEntityName;
    }
}
