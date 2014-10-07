<?php

namespace RZ\Renzo\CMS\Utils;

use Symfony\Component\Security\Core\SecurityContext;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\CMS\Utils\AbstractApi;

use RZ\Renzo\Core\Kernel;

class NodeSourceApi extends AbstractApi
{
    public function getRepository() {
        return Kernel::getService('em')->getRepository("RZ\Renzo\Core\Entities\NodesSources");
    }

    public function getBy( array $criteria, array $order = null, $limit = null, $offset = null ) {
        $context = Kernel::getService('securityContext');
        $result = Kernel::getService('em')->getRepository("RZ\Renzo\Core\Entities\NodesSources")->contextualFindBy($context, $criteria, $order, $limit, $offset);
        return $result;
    }

    public function getOneBy( array $criteria) {
        $context = Kernel::getService('securityContext');
        $result = Kernel::getService('em')->getRepository("RZ\Renzo\Core\Entities\NodesSources")->contextualFindOneBy($context, $criteria);
        return $result;
    }

}