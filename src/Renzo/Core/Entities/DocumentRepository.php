<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file DocumentRepository.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Entities;

use RZ\Renzo\Core\Utils\EntityRepository;

use RZ\Renzo\Core\Entities\Document;
use RZ\Renzo\Core\Entities\NodeTypeField;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Kernel;
/**
 * {@inheritdoc}
 */
class DocumentRepository extends EntityRepository
{
    /**
     * @param RZ\Renzo\Core\Entities\NodesSources  $nodeSource
     * @param RZ\Renzo\Core\Entities\NodeTypeField $field
     *
     * @return array
     */
    public function findByNodeSourceAndField($nodeSource, NodeTypeField $field)
    {
        $query = $this->_em->createQuery('
            SELECT d FROM RZ\Renzo\Core\Entities\Document d
            INNER JOIN d.nodesSourcesByFields nsf
            WHERE nsf.field = :field AND nsf.nodeSource = :nodeSource
            ORDER BY nsf.position ASC')
                        ->setParameter('field', $field)
                        ->setParameter('nodeSource', $nodeSource);
        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param RZ\Renzo\Core\Entities\NodesSources $nodeSource
     * @param string                              $fieldName
     *
     * @return array
     */
    public function findByNodeSourceAndFieldName($nodeSource, $fieldName)
    {
        $query = $this->_em->createQuery('
            SELECT d FROM RZ\Renzo\Core\Entities\Document d
            INNER JOIN d.nodesSourcesByFields nsf
            INNER JOIN nsf.field f
            WHERE f.name = :name AND nsf.nodeSource = :nodeSource
            ORDER BY nsf.position ASC')
                        ->setParameter('name', (string) $fieldName)
                        ->setParameter('nodeSource', $nodeSource);
        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }
}