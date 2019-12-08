<?php
declare(strict_types=1);
/**
 * Copyright (c) 2018. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file FilterQueryBuilderCriteriaEvent.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Core\Events;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\EventDispatcher\Event;

class FilterQueryBuilderCriteriaEvent extends Event
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
     * FilterQueryBuilderEvent constructor.
     *
     * @param QueryBuilder $queryBuilder
     * @param string $entityClass
     * @param string $property
     * @param mixed $value
     * @param $actualEntityName
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
