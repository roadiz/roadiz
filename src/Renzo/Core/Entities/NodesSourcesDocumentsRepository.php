<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file NodesSourcesDocumentsRepository.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Entities;

use RZ\Renzo\Core\Utils\EntityRepository;
use RZ\Renzo\Core\Entities\NodeTypeField;
use RZ\Renzo\Core\Entities\NodesSourcesDocuments;
use RZ\Renzo\Core\Kernel;

/**
 * {@inheritdoc}
 */
class NodesSourcesDocumentsRepository extends EntityRepository
{
    /**
     * @param RZ\Renzo\Core\Entities\NodesSourcesDocument $nodeSource
     * @param RZ\Renzo\Core\Entities\NodeTypeField        $field
     *
     * @return integer
     */
    public function getLatestPosition($nodeSource, NodeTypeField $field)
    {
        $query = $this->_em->createQuery('
            SELECT MAX(nsd.position) FROM RZ\Renzo\Core\Entities\NodesSourcesDocuments nsd
            WHERE nsd.nodeSource = :nodeSource AND nsd.field = :field')
                    ->setParameter('nodeSource', $nodeSource)
                    ->setParameter('field', $field);

        try {
            return (int) $query->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return 0;
        }
    }
}
