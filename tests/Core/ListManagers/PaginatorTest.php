<?php
/**
 * Copyright © 2016, Ambroise Maupate and Julien Blanchet
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
 * @file PaginatorTest.php
 * @author Ambroise Maupate
 */

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Core\ListManagers\NodePaginator;
use RZ\Roadiz\Core\ListManagers\Paginator;
use RZ\Roadiz\Tests\DefaultThemeDependentCase;

class PaginatorTest extends DefaultThemeDependentCase
{
    public function testNodePaginatorTotalCount()
    {
        $paginator = new NodePaginator(
            static::getManager(),
            Node::class
        );
        /** @var \Doctrine\ORM\Query $query */
        $query = static::getManager()->createQuery('SELECT COUNT(n.id) FROM RZ\Roadiz\Core\Entities\Node n');
        $this->assertEquals($query->getSingleScalarResult(), $paginator->getTotalCount());
    }

    /**
     * @dataProvider getTestingItemPerPage
     * @param $itemPerPage
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function testNodePaginatorFindByAtPage($itemPerPage)
    {
        $paginator = new NodePaginator(
            static::getManager(),
            Node::class,
            $itemPerPage
        );

        $query = static::getManager()->createQuery('SELECT COUNT(n.id) FROM RZ\Roadiz\Core\Entities\Node n');

        $nodes = $paginator->findByAtPage();
        $nodesArray = $nodes->getIterator()->getArrayCopy();

        /*
         * If there are more than $itemPerPage nodes
         */
        if ($query->getSingleScalarResult() > $itemPerPage) {
            $this->assertEquals($itemPerPage, count($nodesArray));
        } else {
            $this->assertEquals($query->getSingleScalarResult(), count($nodesArray));
        }
    }

    public function getTestingItemPerPage()
    {
        return [
            [5],
            [10],
            [20],
            [50],
        ];
    }

    public function testPaginatorTotalCount()
    {
        $paginator = new Paginator(
            static::getManager(),
            Role::class
        );

        $query = static::getManager()->createQuery('SELECT COUNT(d.id) FROM RZ\Roadiz\Core\Entities\Role d');

        $this->assertEquals($query->getSingleScalarResult(), $paginator->getTotalCount());
    }

    /**
     * @dataProvider getTestingItemPerPage
     * @param $itemPerPage
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function testPaginatorFindByAtPage($itemPerPage)
    {
        $paginator = new Paginator(
            static::getManager(),
            Role::class,
            $itemPerPage
        );

        $query = static::getManager()->createQuery('SELECT COUNT(d.id) FROM RZ\Roadiz\Core\Entities\Role d');

        /*
         * If there are more than $itemPerPage nodes
         */
        if ($query->getSingleScalarResult() > $itemPerPage) {
            $this->assertEquals($itemPerPage, count($paginator->findByAtPage()));
        } else {
            $this->assertEquals($query->getSingleScalarResult(), count($paginator->findByAtPage()));
        }
    }
}
