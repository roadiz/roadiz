<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Utils;

use Doctrine\ORM\Tools\Pagination\Paginator;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Repositories\NodeRepository;

/**
 * @package RZ\Roadiz\CMS\Utils
 */
class NodeApi extends AbstractApi
{
    /**
     * @return NodeRepository
     */
    public function getRepository()
    {
        // phpstan cannot resolve repository type.
        /** @var NodeRepository $repository */
        $repository = $this->managerRegistry
                    ->getRepository(Node::class)
                    ->setDisplayingNotPublishedNodes(false)
                    ->setDisplayingAllNodesStatuses(false);
        return $repository;
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
        ?int $limit = null,
        ?int $offset = null
    ) {
        if (!in_array('translation.available', $criteria, true)) {
            $criteria['translation.available'] = true;
        }

        return $this->getRepository()
                    ->findBy(
                        $criteria,
                        $order,
                        $limit,
                        $offset,
                        null
                    );
    }
    /**
     * {@inheritdoc}
     */
    public function countBy(array $criteria)
    {
        if (!in_array('translation.available', $criteria, true)) {
            $criteria['translation.available'] = true;
        }

        return $this->getRepository()
                    ->countBy(
                        $criteria,
                        null
                    );
    }
    /**
     * {@inheritdoc}
     */
    public function getOneBy(array $criteria, array $order = null)
    {
        if (!in_array('translation.available', $criteria, true)) {
            $criteria['translation.available'] = true;
        }

        return $this->getRepository()
                    ->findOneBy(
                        $criteria,
                        $order,
                        null
                    );
    }
}
