<?php

namespace RZ\Renzo\CMS\Utils;

use Symfony\Component\Security\Core\SecurityContext;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\CMS\Utils\AbstractApi;

use RZ\Renzo\Core\Kernel;

class NodeTypeApi extends AbstractApi
{
    public function getRepository() {
        return Kernel::getService('em')->getRepository("RZ\Renzo\Core\Entities\NodeType");
    }

    public function getBy( array $criteria, array $order = null) {
        $result = Kernel::getService('em')->getRepository("RZ\Renzo\Core\Entities\NodeType")->findBy($criteria, $order);
        return $result;
    }

    public function getOneBy( array $criteria, array $order = null) {
        $result = Kernel::getService('em')->getRepository("RZ\Renzo\Core\Entities\NodeType")->findOneBy($criteria, $order);
        return $result;
    }

}