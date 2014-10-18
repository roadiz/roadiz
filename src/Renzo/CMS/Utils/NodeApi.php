<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 *
 * @file NodeApi.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\CMS\Utils;

use RZ\Renzo\CMS\Utils\AbstractApi;

/**
 *
 */
class NodeApi extends AbstractApi
{
    public function getRepository()
    {
        return $this->container['em']->getRepository("RZ\Renzo\Core\Entities\Node");
    }

    public function getBy(array $criteria, array $order = null, $limit = null, $offset = null)
    {
        return $this->container['em']
                    ->getRepository("RZ\Renzo\Core\Entities\Node")
                    ->findBy(
                        $criteria,
                        $order,
                        $limit,
                        $offset,
                        null,
                        $this->container['securityContext']
                    );
    }

    public function getOneBy(array $criteria, array $order = null)
    {
        return $this->container['em']
                    ->getRepository("RZ\Renzo\Core\Entities\Node")
                    ->findOneBy(
                        $criteria,
                        $order,
                        null,
                        $this->container['securityContext']
                    );
    }
}
