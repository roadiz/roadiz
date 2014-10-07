<?php

namespace RZ\Renzo\CMS\Utils;

use Symfony\Component\Security\Core\SecurityContext;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\CMS\Utils\AbstractApi;

use RZ\Renzo\Core\Kernel;

class NodeSourceApi extends AbstractApi
{
    public function getRepository() {
        return Kernel::getService('em')->getRepository("RZ\Renzo\Core\Entities\NodesSources");
    }

    public function getBy( array $criteria, array $order = null, $limit = null, $offset = null ) {
        $rep = null;
        if (isset($criteria['node.nodeType'])) {
            $rep = NodeType::getGeneratedEntitiesNamespace()."\\".$criteria['node.nodeType']->getSourceEntityClassName();
            unset($criteria['node.nodeType']);
        }
        else {
            $rep = "RZ\Renzo\Core\Entities\NodesSources";
        }
        $result = Kernel::getService('em')->getRepository($rep)->findBy($criteria, $order, $limit, $offset, $this->context);
        return $result;
    }

    public function getOneBy( array $criteria, array $order = null) {
        $result = Kernel::getService('em')->getRepository("RZ\Renzo\Core\Entities\NodesSources")->findOneBy($criteria, $order, $this->context);
        return $result;
    }

}