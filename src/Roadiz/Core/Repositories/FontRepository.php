<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Repositories;

/**
 * @package RZ\Roadiz\Core\Repositories
 * @extends EntityRepository<\RZ\Roadiz\Core\Entities\Font>
 */
class FontRepository extends EntityRepository
{
    public function getLatestUpdateDate()
    {
        $query = $this->_em->createQuery('
            SELECT MAX(f.updatedAt) FROM RZ\Roadiz\Core\Entities\Font f');

        return $query->setQueryCacheLifetime(0)->getSingleScalarResult();
    }
}
