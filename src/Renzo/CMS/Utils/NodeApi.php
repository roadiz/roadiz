<?php
/**
 * Copyright © 2014, REZO ZERO
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
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file NodeApi.php
 * @copyright REZO ZERO 2014
 * @author Maxime Constantinian
 */
namespace RZ\Renzo\CMS\Utils;

use RZ\Renzo\CMS\Utils\AbstractApi;
use RZ\Renzo\Core\Entities\Node;

/**
 *
 */
class NodeApi extends AbstractApi
{
    public function getRepository()
    {
        return $this->container['em']->getRepository("RZ\Renzo\Core\Entities\Node");
    }

    public function getBy(
        array $criteria,
        array $order = null,
        $limit = null,
        $offset = null
    ) {
        if (empty($criteria['status'])) {
            $criteria['status'] = array('<=', Node::PUBLISHED);
        }

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

    public function countBy(array $criteria)
    {
        if (empty($criteria['status'])) {
            $criteria['status'] = array('<=', Node::PUBLISHED);
        }

        return $this->container['em']
                    ->getRepository("RZ\Renzo\Core\Entities\Node")
                    ->countBy(
                        $criteria,
                        null,
                        $this->container['securityContext']
                    );
    }

    public function getOneBy(array $criteria, array $order = null)
    {
        if (empty($criteria['status'])) {
            $criteria['status'] = array('<=', Node::PUBLISHED);
        }

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
