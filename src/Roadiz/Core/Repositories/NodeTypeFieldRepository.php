<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
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
 * @file NodeTypeFieldRepository.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Repositories;

use Doctrine\ORM\NoResultException;
use RZ\Roadiz\Core\Entities\NodeType;

class NodeTypeFieldRepository extends EntityRepository
{
    public function findAvailableGroupsForNodeType(NodeType $nodeType = null)
    {
        $query = $this->_em->createQuery('
            SELECT partial ntf.{id,groupName} FROM RZ\Roadiz\Core\Entities\NodeTypeField ntf
            WHERE ntf.visible = true
            AND ntf.nodeType = :nodeType
            GROUP BY ntf.groupName
            ORDER BY ntf.groupName ASC
        ')->setParameter(':nodeType', $nodeType);

        try {
            return $query->getScalarResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * Get latest position in nodeType.
     *
     * Parent can be null for tag root
     *
     * @param  NodeType|null $nodeType
     *
     * @return int
     */
    public function findLatestPositionInNodeType(NodeType $nodeType)
    {
        $query = $this->_em->createQuery('
            SELECT MAX(ntf.position)
            FROM RZ\Roadiz\Core\Entities\NodeTypeField ntf
            WHERE ntf.nodeType = :nodeType')
            ->setParameter('nodeType', $nodeType);

        try {
            return $query->getSingleScalarResult();
        } catch (NoResultException $e) {
            return null;
        }
    }
}
