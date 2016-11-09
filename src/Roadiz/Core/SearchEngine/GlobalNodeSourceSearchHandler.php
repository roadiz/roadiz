<?php
/**
 * Copyright (c) 2016. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file GlobalNodeSourceSearchHandler.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace RZ\Roadiz\Core\SearchEngine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Translation;

/**
 * Class GlobalNodeSourceSearchHandler
 * @package RZ\Roadiz\Core\SearchEngine
 */
class GlobalNodeSourceSearchHandler
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * GlobalNodeSourceSearchHandler constructor.
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param string $searchTerm
     * @param int $resultCount
     * @param Translation $translation
     * @return NodesSources[]
     */
    public function getNodeSourcesBySearchTerm($searchTerm, $resultCount, Translation $translation)
    {
        $safeSearchTerms = strip_tags($searchTerm);

        /*
         * First try with Solr
         */
        /** @var array $nodesSources */
        $nodesSources = $this->em
            ->getRepository('RZ\Roadiz\Core\Entities\NodesSources')
            ->findBySearchQuery(
                $safeSearchTerms,
                $resultCount
            );

        /*
         * Second try with sources fields
         */
        if (count($nodesSources) === 0) {
            $nodesSources = $this->em
                ->getRepository('RZ\Roadiz\Core\Entities\NodesSources')
                ->searchBy(
                    $safeSearchTerms,
                    [],
                    [],
                    $resultCount
                );

            if (count($nodesSources) === 0) {
                /*
                 * Then try with node name.
                 */
                /** @var QueryBuilder $qb */
                $qb = $this->em
                    ->getRepository('RZ\Roadiz\Core\Entities\NodesSources')
                    ->createQueryBuilder('ns');

                $qb->select('ns, n')
                    ->innerJoin('ns.node', 'n')
                    ->andWhere($qb->expr()->like('n.nodeName', ':nodeName'))
                    ->andWhere($qb->expr()->eq('ns.translation', ':translation'))
                    ->setMaxResults($resultCount)
                    ->setParameter('translation', $translation)
                    ->setParameter('nodeName', '%' . $safeSearchTerms . '%');

                try {
                    return $qb->getQuery()->getResult();
                } catch (NoResultException $e) {
                    return [];
                }
            }
        }

        return $nodesSources;
    }
}
