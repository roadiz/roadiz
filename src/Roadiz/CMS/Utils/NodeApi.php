<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Utils;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Repositories\NodeRepository;

/**
 * Class NodeApi.
 *
 * @package RZ\Roadiz\CMS\Utils
 */
class NodeApi extends AbstractApi
{
    /**
     * @return NodeRepository
     */
    public function getRepository()
    {
        return $this->container['em']
                    ->getRepository(Node::class)
                    ->setDisplayingNotPublishedNodes(false)
                    ->setDisplayingAllNodesStatuses(false);
    }
    /**
     * {@inheritdoc}
     */
    public function getBy(
        array $criteria,
        array $order = null,
        $limit = null,
        $offset = null
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
