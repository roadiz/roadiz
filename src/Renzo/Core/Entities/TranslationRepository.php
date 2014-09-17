<?php
/*
 * Copyright REZO ZERO 2014
 *
 * @file TranslationRepository.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Entities;

use RZ\Renzo\Core\Utils\EntityRepository;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Kernel;

/**
 * {@inheritdoc}
 */
class TranslationRepository extends EntityRepository
{
    /**
     * Get single default translation.
     *
     * @return Translation
     */
    public function findDefault()
    {
        $query = $this->_em->createQuery('
            SELECT t FROM RZ\Renzo\Core\Entities\Translation t
            WHERE t.defaultTranslation = true
            AND t.available = true
        ');

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * Get all available translations.
     *
     * @return ArrayCollection
     */
    public function findAllAvailable()
    {
        $query = $this->_em->createQuery('
            SELECT t FROM RZ\Renzo\Core\Entities\Translation t
            WHERE t.available = true
        ');

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param string $locale
     *
     * @return boolean
     */
    public function exists($locale)
    {
        $query = $this->_em->createQuery('
            SELECT COUNT(t.locale) FROM RZ\Renzo\Core\Entities\Translation t
            WHERE t.locale = :locale
        ')->setParameter('locale', $locale);

        try {
            return (boolean) $query->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return false;
        }
    }
}
