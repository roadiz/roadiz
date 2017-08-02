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
 * @file NodeTypeRepository.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Repositories;

use Doctrine\ORM\NoResultException;

/**
 * {@inheritdoc}
 */
class NodeTypeRepository extends EntityRepository
{
    /**
     * @return array
     */
    public function findAll()
    {
        $qb = $this->createQueryBuilder('nt');
        $qb->addSelect('ntf')
            ->leftJoin('nt.fields', 'ntf')
            ->addOrderBy('nt.name', 'ASC')
            ->setCacheable(true);

        $query = $qb->getQuery();

        try {
            return $query->getResult();
        } catch (NoResultException $e) {
            return [];
        }
    }

    /**
     * Get all newsletter node-types.
     *
     * @return array
     */
    public function findAllNewsletterType()
    {
        $qb = $this->createQueryBuilder('nt');
        $qb->addSelect('ntf')
            ->innerJoin('nt.fields', 'ntf')
            ->andWhere($qb->expr()->eq('nt.newsletterType', true))
            ->setCacheable(true);

        try {
            return $qb->getQuery()->getResult();
        } catch (NoResultException $e) {
            return [];
        }
    }
}
