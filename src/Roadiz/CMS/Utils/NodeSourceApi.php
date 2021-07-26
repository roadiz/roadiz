<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Utils;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Repositories\NodesSourcesRepository;

/**
 * @package RZ\Roadiz\CMS\Utils
 */
class NodeSourceApi extends AbstractApi
{
    /**
     * @var class-string
     */
    protected string $repository = NodesSources::class;

    /**
     * @param array|null $criteria
     * @return string
     */
    protected function getRepositoryName(array $criteria = null)
    {
        if (isset($criteria['node.nodeType']) && $criteria['node.nodeType'] instanceof NodeType) {
            $this->repository = $criteria['node.nodeType']->getSourceEntityFullQualifiedClassName();
            unset($criteria['node.nodeType']);
        } elseif (isset($criteria['node.nodeType']) &&
            is_array($criteria['node.nodeType']) &&
            count($criteria['node.nodeType']) === 1 &&
            $criteria['node.nodeType'][0] instanceof NodeType) {
            $this->repository = $criteria['node.nodeType'][0]->getSourceEntityFullQualifiedClassName();
            unset($criteria['node.nodeType']);
        } else {
            $this->repository = NodesSources::class;
        }

        return $this->repository;
    }

    /**
     * @return NodesSourcesRepository|EntityRepository
     */
    public function getRepository()
    {
        return $this->managerRegistry->getRepository($this->repository);
    }

    /**
     * @param array $criteria
     * @param array|null $order
     * @param int|null $limit
     * @param int|null $offset
     * @return array|Paginator
     */
    public function getBy(
        array $criteria,
        array $order = null,
        $limit = null,
        $offset = null
    ) {
        $this->getRepositoryName($criteria);

        return $this->getRepository()
                    ->findBy(
                        $criteria,
                        $order,
                        $limit,
                        $offset
                    );
    }

    /**
     * @param array $criteria
     * @return int
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countBy(
        array $criteria
    ) {
        $this->getRepositoryName($criteria);

        return $this->getRepository()
                    ->countBy(
                        $criteria
                    );
    }

    /**
     * @param array $criteria
     * @param array|null $order
     * @return null|NodesSources
     */
    public function getOneBy(array $criteria, array $order = null)
    {
        $this->getRepositoryName($criteria);

        return $this->getRepository()
                    ->findOneBy(
                        $criteria,
                        $order
                    );
    }

    /**
     * Search Nodes-Sources using LIKE condition on title,
     * meta-title, meta-keywords and meta-description.
     *
     * @param string $textQuery
     * @param int $limit
     * @param array $nodeTypes
     * @param bool $onlyVisible
     * @param array $additionalCriteria
     * @return array
     */
    public function searchBy(
        string $textQuery,
        int $limit = 0,
        array $nodeTypes = [],
        bool $onlyVisible = false,
        array $additionalCriteria = []
    ) {
        $repository = $this->getRepository();

        if ($repository instanceof NodesSourcesRepository) {
            return $this->getRepository()
                ->findByTextQuery(
                    $textQuery,
                    $limit,
                    $nodeTypes,
                    $onlyVisible,
                    $additionalCriteria
                );
        }

        return [];
    }
}
