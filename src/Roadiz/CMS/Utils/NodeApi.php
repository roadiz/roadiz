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
 * @file NodeApi.php
 * @author Maxime Constantinian
 */
namespace RZ\Roadiz\CMS\Utils;

use RZ\Roadiz\CMS\Utils\AbstractApi;
use RZ\Roadiz\Core\Entities\Node;

/**
 *
 */
class NodeApi extends AbstractApi
{
    /**
     * {@inheritdoc}
     */
    public function getRepository()
    {
        return $this->container['em']->getRepository("RZ\Roadiz\Core\Entities\Node");
    }
    /**
     * {@inheritdoc}
     */
    public function getBy(
        array $criteria,
        array $order = null,
        $limit = null,
        $offset = null
    ) {
        if (empty($criteria['status'])) {
            $criteria['status'] = array('<=', Node::PUBLISHED);
        }
        if (!in_array('translation.available', $criteria, true)) {
            $criteria['translation.available'] = true;
        }

        return $this->getRepository()
                    ->findBy(
                        $criteria,
                        $order,
                        $limit,
                        $offset,
                        null,
                        $this->container['securityContext']
                    );
    }
    /**
     * {@inheritdoc}
     */
    public function countBy(array $criteria)
    {
        if (empty($criteria['status'])) {
            $criteria['status'] = array('<=', Node::PUBLISHED);
        }
        if (!in_array('translation.available', $criteria, true)) {
            $criteria['translation.available'] = true;
        }

        return $this->getRepository()
                    ->countBy(
                        $criteria,
                        null,
                        $this->container['securityContext']
                    );
    }
    /**
     * {@inheritdoc}
     */
    public function getOneBy(array $criteria, array $order = null)
    {
        if (empty($criteria['status'])) {
            $criteria['status'] = array('<=', Node::PUBLISHED);
        }
        if (!in_array('translation.available', $criteria, true)) {
            $criteria['translation.available'] = true;
        }

        return $this->getRepository()
                    ->findOneBy(
                        $criteria,
                        $order,
                        null,
                        $this->container['securityContext']
                    );
    }
}
