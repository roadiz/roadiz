<?php

namespace RZ\Renzo\CMS\Utils;

use Symfony\Component\Security\Core\SecurityContext;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\CMS\Utils\AbstractApi;

use RZ\Renzo\Core\Kernel;

class NodeApi extends AbstractApi
{
    public function getRepository() {
        return Kernel::getService('em')->getRepository("RZ\Renzo\Core\Entities\Node");
    }

    public function getBy( array $criteria, array $order = null, $limit = null, $offset = null ) {
        $result = Kernel::getService('em')->getRepository("RZ\Renzo\Core\Entities\Node")->findBy($criteria, $order, $limit, $offset);
        return $result;
    }

    public function getOneBy( array $criteria) {
        $result = Kernel::getService('em')->getRepository("RZ\Renzo\Core\Entities\Node")->findOneBy($criteria);
        return $result;
    }

}