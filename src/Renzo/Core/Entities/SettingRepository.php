<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file SettingRepository.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Entities;

use RZ\Renzo\Core\Utils\EntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\AbstractEntity;
use RZ\Renzo\Core\Utils\StringHandler;
use RZ\Renzo\Core\Kernel;
/**
 * {@inheritdoc}
 */
class SettingRepository extends EntityRepository
{
    /**
     * Return Setting raw value.
     *
     * @param string $name
     *
     * @return string
     */
    public function getValue($name)
    {
        $query = $this->_em->createQuery('
            SELECT s.value FROM RZ\Renzo\Core\Entities\Setting s
            WHERE s.name = :name')
                        ->setParameter('name', $name);

        try {
            return $query->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param string $name
     *
     * @return boolean
     */
    public function exists($name)
    {
        $query = $this->_em->createQuery('
            SELECT COUNT(s.value) FROM RZ\Renzo\Core\Entities\Setting s
            WHERE s.name = :name')
                        ->setParameter('name', $name);

        try {
            return (boolean) $query->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return false;
        }
    }
}