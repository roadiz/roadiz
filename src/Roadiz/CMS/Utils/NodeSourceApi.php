<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file NodeSourceApi.php
 * @author Maxime Constantinian
 */
namespace RZ\Roadiz\CMS\Utils;

use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\CMS\Utils\AbstractApi;

/**
 *
 */
class NodeSourceApi extends AbstractApi
{
    public function getRepository()
    {
        return $this->container['em']->getRepository("RZ\Roadiz\Core\Entities\NodesSources");
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
            $rep = "RZ\Roadiz\Core\Entities\NodesSources";
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

    public function countBy(
        array $criteria
    ) {
        if (empty($criteria['node.status'])) {
            $criteria['node.status'] = array('<=', Node::PUBLISHED);
        }

        $rep = $this->getRepositoryName($criteria);

        return $this->container['em']
                    ->getRepository($rep)
                    ->countBy(
                        $criteria,
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
