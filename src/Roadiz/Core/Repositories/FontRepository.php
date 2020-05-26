<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Repositories;

class FontRepository extends EntityRepository
{
    public function getLatestUpdateDate()
    {
        $query = $this->_em->createQuery('
            SELECT MAX(f.updatedAt) FROM RZ\Roadiz\Core\Entities\Font f');

        return $query->getSingleScalarResult();
    }
}
