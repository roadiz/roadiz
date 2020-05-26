<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Utils;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Pimple\Container;

/**
 * Class AbstractApi.
 *
 * @package RZ\Roadiz\CMS\Utils
 */
abstract class AbstractApi
{
    /**
     * DI container
     *
     * @var Container $container
     */
    protected $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Return entity path for current API.
     *
     * @return \Doctrine\ORM\EntityRepository
     */
    abstract public function getRepository();

    /**
     * Return an array of entities matching criteria array.
     *
     * @param array $criteria
     * @return array|Paginator
     */
    abstract public function getBy(array $criteria);

    /**
     * Return one entity matching criteria array.
     *
     * @param array $criteria
     *
     * @return mixed
     */
    abstract public function getOneBy(array $criteria);

    /**
     * Count entities matching criteria array.
     *
     * @param array $criteria
     *
     * @return int
     */
    abstract public function countBy(array $criteria);
}
