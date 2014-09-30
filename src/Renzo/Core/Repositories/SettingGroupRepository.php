<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file SettingGroupRepository.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Repositories;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\AbstractEntity;
use RZ\Renzo\Core\Utils\StringHandler;
use RZ\Renzo\Core\Kernel;

/**
 * {@inheritdoc}
 */
class SettingGroupRepository extends EntityRepository
{

    /**
     * @param string $name
     *
     * @return boolean
     */
    public function exists($name)
    {
        $query = $this->_em->createQuery('
            SELECT COUNT(s.id) FROM RZ\Renzo\Core\Entities\SettingGroup s
            WHERE s.name = :name')
                        ->setParameter('name', $name);

        try {
            return (boolean) $query->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return false;
        }
    }

    /**
     * @param string $name
     *
     * @return boolean
     */
    public function findAllNames()
    {
        $query = $this->_em->createQuery('SELECT s.name FROM RZ\Renzo\Core\Entities\SettingGroup s');
        try {
            $result = $query->getScalarResult();

            $ids = array();
            foreach ($result as $item) {
                $ids[] = $item['name'];
            }

            return $ids;

        } catch (\Doctrine\ORM\NoResultException $e) {
            return false;
        }
    }
}
