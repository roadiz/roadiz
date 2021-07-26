<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Utils;

use Doctrine\ORM\Tools\Pagination\Paginator;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Repositories\TagRepository;

/**
 * @package RZ\Roadiz\CMS\Utils
 */
class TagApi extends AbstractApi
{
    /**
     * @return TagRepository
     */
    public function getRepository()
    {
        return $this->managerRegistry->getRepository(Tag::class);
    }

    /**
     * Get tags using criteria, orders, limit and offset.
     *
     * When no order is defined, tags are ordered by position.
     *
     * @param array      $criteria
     * @param array|null $order
     * @param int|null   $limit
     * @param int|null   $offset
     *
     * @return array|Paginator
     */
    public function getBy(
        array $criteria,
        array $order = null,
        $limit = null,
        $offset = null
    ) {
        if (null === $order) {
            $order = [
                'position' => 'ASC',
            ];
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
        return $this->getRepository()
                    ->findOneBy(
                        $criteria,
                        $order,
                        null
                    );
    }
}
