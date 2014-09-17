<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file UrlAliasRepository.php
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
class UrlAliasRepository extends EntityRepository
{
    /**
     * Get all url aliases linked to given node.
     *
     * @param integer $nodeId
     *
     * @return array
     */
    public function findAllFromNode($nodeId)
    {
        $query = $this->_em->createQuery('
            SELECT ua FROM RZ\Renzo\Core\Entities\UrlAlias ua
            INNER JOIN ua.nodeSource ns
            INNER JOIN ns.node n
            WHERE n.id = :nodeId')
                        ->setParameter('nodeId', (int) $nodeId);

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param string $alias
     *
     * @return boolean
     */
    public function exists($alias)
    {
        $query = $this->_em->createQuery('
            SELECT COUNT(ua.alias) FROM RZ\Renzo\Core\Entities\UrlAlias ua
            WHERE ua.alias = :alias')
                        ->setParameter('alias', $alias);

        try {
            return (boolean) $query->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return false;
        }
    }
}
