<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 *
 * @file NodeTypeApi.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\CMS\Utils;

use RZ\Renzo\CMS\Utils\AbstractApi;

/**
 *
 */
class NodeTypeApi extends AbstractApi
{
    public function getRepository()
    {
        return $this->container['em']
                    ->getRepository("RZ\Renzo\Core\Entities\NodeType");
    }

    public function getBy(array $criteria, array $order = null)
    {
        return $this->container['em']
                    ->getRepository("RZ\Renzo\Core\Entities\NodeType")
                    ->findBy($criteria, $order);
    }

    public function getOneBy(array $criteria, array $order = null)
    {
        return  $this->container['em']
                     ->getRepository("RZ\Renzo\Core\Entities\NodeType")
                     ->findOneBy($criteria, $order);
    }
}
