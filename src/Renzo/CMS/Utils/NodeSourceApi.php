<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 *
 * @file NodeSourceApi.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\CMS\Utils;

use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\CMS\Utils\AbstractApi;

/**
 *
 */
class NodeSourceApi extends AbstractApi
{
    public function getRepository()
    {
        return $this->container['em']->getRepository("RZ\Renzo\Core\Entities\NodesSources");
    }

    private function getRepositoryName($criteria)
    {
        $rep = null;
        if (isset($criteria['node.nodeType'])) {
            $rep = NodeType::getGeneratedEntitiesNamespace().
                   "\\".
                   $criteria['node.nodeType']->getSourceEntityClassName();

            unset($criteria['node.nodeType']);
        } else {
            $rep = "RZ\Renzo\Core\Entities\NodesSources";
        }
        return $rep;
    }

    public function getBy(
        array $criteria,
        array $order = null,
        $limit = null,
        $offset = null
    ) {
        if (empty($criteria['node.status'])) {
            $criteria['node.status'] = array('<=', Node::PUBLISHED);
        }

        $rep = $this->getRepositoryName($criteria);

        return $this->container['em']
                    ->getRepository($rep)
                    ->findBy(
                        $criteria,
                        $order,
                        $limit,
                        $offset,
                        $this->container['securityContext']
                    );
    }

    public function getOneBy(array $criteria, array $order = null)
    {
        if (empty($criteria['node.status'])) {
            $criteria['node.status'] = array('<=', Node::PUBLISHED);
        }
        $rep = $this->getRepositoryName($criteria);

        return $this->container['em']
                    ->getRepository($rep)
                    ->findOneBy(
                        $criteria,
                        $order,
                        $this->container['securityContext']
                    );
    }
}
