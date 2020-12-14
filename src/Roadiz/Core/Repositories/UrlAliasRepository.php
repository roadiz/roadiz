<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Repositories;

/**
 * @package RZ\Roadiz\Core\Repositories
 * @extends EntityRepository<\RZ\Roadiz\Core\Entities\UrlAlias>
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
            SELECT ua FROM RZ\Roadiz\Core\Entities\UrlAlias ua
            INNER JOIN ua.nodeSource ns
            INNER JOIN ns.node n
            WHERE n.id = :nodeId')
                        ->setParameter('nodeId', (int) $nodeId);

        return $query->getResult();
    }

    /**
     * @param string $alias
     *
     * @return boolean
     */
    public function exists($alias)
    {
        $query = $this->_em->createQuery('
            SELECT COUNT(ua.alias) FROM RZ\Roadiz\Core\Entities\UrlAlias ua
            WHERE ua.alias = :alias')
                        ->setParameter('alias', $alias);

        return (boolean) $query->getSingleScalarResult();
    }
}
