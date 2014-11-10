<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file CustomFormRepository.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Repositories;

use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Kernel;

/**
 * {@inheritdoc}
 */
class CustomFormRepository extends EntityRepository
{
    /**
     * Get all custom-form names from PARTIAL objects.
     *
     * @return ArrayCollection
     */
    public function findAllNames()
    {
        $query = $this->_em->createQuery('
            SELECT partial nt.{id,name} FROM RZ\Renzo\Core\Entities\CustomForm nt');

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }
}
