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
class TagApi extends AbstractApi
{
    public function getRepository()
    {
        return $this->container['em']->getRepository("RZ\Renzo\Core\Entities\Tag");
    }

    public function getBy(array $criteria, array $order = null, $limit = null, $offset = null)
    {
        return $this->getRepository()
                    ->findBy(
                        $criteria,
                        $order,
                        $limit,
                        $offset,
                        null
                    );
    }

    public function getOneBy(array $criteria, array $order = null)
    {
        return $this->getRepository()
                    ->findOneBy(
                        $criteria,
                        $order,
                        null
                    );
    }
}
