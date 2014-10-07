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
use RZ\Renzo\CMS\Utils\AbstractApi;

/**
 *
 */
class NodeSourceApi extends AbstractApi
{
    public function getRepository() {
        return $this->container['em']->getRepository("RZ\Renzo\Core\Entities\NodesSources");
    }

    public function getBy(
        array $criteria,
        array $order = null,
        $limit = null,
        $offset = null
    ) {
        $rep = null;
        if (isset($criteria['node.nodeType'])) {
            $rep = NodeType::getGeneratedEntitiesNamespace().
                   "\\".
                   $criteria['node.nodeType']->getSourceEntityClassName();

            unset($criteria['node.nodeType']);
        }
        else {
            $rep = "RZ\Renzo\Core\Entities\NodesSources";
        }

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

    public function getOneBy(array $criteria, array $order = null) {
        return $this->container['em']
                       ->getRepository("RZ\Renzo\Core\Entities\NodesSources")
                       ->findOneBy(
                            $criteria,
                            $order,
                            $this->container['securityContext']
                        );
    }
}
