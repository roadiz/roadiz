<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Repositories;

use RZ\Roadiz\Core\Entities\TagTranslation;

/**
 * Class TagTranslationDocumentsRepository
 *
 * @package RZ\Roadiz\Core\Repositories
 */
class TagTranslationDocumentsRepository extends EntityRepository
{
    /**
     * @param TagTranslation $tagTranslation
     *
     * @return integer
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLatestPosition($tagTranslation)
    {
        $query = $this->_em->createQuery('SELECT MAX(ttd.position)
FROM RZ\Roadiz\Core\Entities\TagTranslationDocuments ttd
WHERE ttd.tagTranslation = :tagTranslation')
                    ->setParameter('tagTranslation', $tagTranslation);

        return (int) $query->getSingleScalarResult();
    }
}
