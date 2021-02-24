<?php
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Repository;

use RZ\Roadiz\Attribute\Model\AttributableInterface;
use RZ\Roadiz\Core\AbstractEntities\TranslationInterface;
use RZ\Roadiz\Core\Repositories\EntityRepository;

final class AttributeValueRepository extends EntityRepository
{
    /**
     * @param AttributableInterface $attributable
     *
     * @return array
     */
    public function findByAttributable(
        AttributableInterface $attributable
    ): array {
        $qb = $this->createQueryBuilder('av');
        return $qb->addSelect('avt')
            ->addSelect('a')
            ->addSelect('at')
            ->addSelect('ad')
            ->addSelect('ag')
            ->addSelect('agt')
            ->innerJoin('av.attributeValueTranslations', 'avt')
            ->innerJoin('av.attribute', 'a')
            ->leftJoin('a.attributeDocuments', 'ad')
            ->leftJoin('a.attributeTranslations', 'at')
            ->leftJoin('a.group', 'ag')
            ->leftJoin('ag.attributeGroupTranslations', 'agt')
            ->andWhere($qb->expr()->eq('av.node', ':attributable'))
            ->addOrderBy('av.position', 'ASC')
            ->setParameters([
                'attributable' => $attributable,
            ])
            ->setCacheable(true)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param AttributableInterface $attributable
     * @param TranslationInterface  $translation
     *
     * @return array
     */
    public function findByAttributableAndTranslation(
        AttributableInterface $attributable,
        TranslationInterface $translation
    ): array {
        $qb = $this->createQueryBuilder('av');
        return $qb->addSelect('avt')
            ->addSelect('a')
            ->addSelect('at')
            ->addSelect('ad')
            ->addSelect('ag')
            ->addSelect('agt')
            ->innerJoin('av.attributeValueTranslations', 'avt')
            ->innerJoin('av.attribute', 'a')
            ->leftJoin('a.attributeTranslations', 'at')
            ->leftJoin('a.attributeDocuments', 'ad')
            ->leftJoin('a.group', 'ag')
            ->leftJoin('ag.attributeGroupTranslations', 'agt')
            ->andWhere($qb->expr()->eq('av.node', ':attributable'))
            ->andWhere($qb->expr()->eq('at.translation', ':translation'))
            ->andWhere($qb->expr()->eq('agt.translation', ':translation'))
            ->addOrderBy('av.position', 'ASC')
            ->setParameters([
                'attributable' => $attributable,
                'translation' => $translation
            ])
            ->setCacheable(true)
            ->getQuery()
            ->getResult();
    }
}
