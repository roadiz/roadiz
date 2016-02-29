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
 * @file UrlAliasRepository.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Repositories;
use Doctrine\ORM\NoResultException;

/**
 * {@inheritdoc}
 */
class UrlAliasRepository extends EntityRepository
{
    /**
     * Get all url aliases linked to given node.
     *
     * @param integer $nodeId
     *
     * @return array
     */
    public function findAllFromNode($nodeId)
    {
        $query = $this->_em->createQuery('
            SELECT ua FROM RZ\Roadiz\Core\Entities\UrlAlias ua
            INNER JOIN ua.nodeSource ns
            INNER JOIN ns.node n
            WHERE n.id = :nodeId')
                        ->setParameter('nodeId', (int) $nodeId);

        try {
            return $query->getResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * Get all url aliases linked to given node.
     *
     * @param $alias
     *
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOnePartialByAlias($alias)
    {
        $query = $this->_em->createQuery('
            SELECT partial ua.{id} FROM RZ\Roadiz\Core\Entities\UrlAlias ua
            WHERE ua.alias = :alias')
                        ->setParameter('alias', $alias);

        try {
            return $query->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * @param string $alias
     *
     * @return boolean
     */
    public function exists($alias)
    {
        $query = $this->_em->createQuery('
            SELECT COUNT(ua.alias) FROM RZ\Roadiz\Core\Entities\UrlAlias ua
            WHERE ua.alias = :alias')
                        ->setParameter('alias', $alias);

        try {
            return (boolean) $query->getSingleScalarResult();
        } catch (NoResultException $e) {
            return false;
        }
    }
}
