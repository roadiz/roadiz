<?php
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Repository;

use RZ\Roadiz\Attribute\Model\AttributableInterface;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Repositories\EntityRepository;
use function Doctrine\ORM\QueryBuilder;

final class AttributeValueRepository extends EntityRepository
{
    /**
     * @param AttributableInterface $attributable
     * @param Translation           $translation
     *
     * @return array
     */
    public function findByAttributableAndTranslation(
        AttributableInterface $attributable,
        Translation $translation
    ): array {
        $qb = $this->createQueryBuilder('av');
        return $qb->addSelect('a')
            ->addSelect('at')
            ->addSelect('avt')
            ->addSelect('ag')
            ->addSelect('agt')
            ->leftJoin('av.attributeValueTranslations', 'avt')
            ->innerJoin('av.attribute', 'a')
            ->leftJoin('a.attributeTranslations', 'at')
            ->leftJoin('a.group', 'ag')
            ->leftJoin('ag.attributeGroupTranslations', 'agt')
            ->andWhere($qb->expr()->eq('av.node', ':attributable'))
            ->andWhere($qb->expr()->eq('avt.translation', ':translation'))
            ->andWhere($qb->expr()->eq('agt.translation', ':translation'))
            ->andWhere($qb->expr()->eq('at.translation', ':translation'))
            ->setParameters([
                'translation' => $translation,
                'attributable' => $attributable
            ])
            ->setCacheable(true)
            ->getQuery()
            ->getResult();
    }
}
