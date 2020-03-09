<?php
/**
 * Copyright (c) 2020. Ambroise Maupate and Julien Blanchet
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
 * @file LoginAttemptRepository.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
declare(strict_types=1);

namespace RZ\Roadiz\Core\Repositories;

use RZ\Roadiz\Core\Entities\LoginAttempt;

class LoginAttemptRepository extends EntityRepository
{
    /**
     * @param string $username
     *
     * @return bool
     */
    public function isUsernameBlocked(string $username): bool
    {
        $qb = $this->createQueryBuilder('la');
        return $qb->select('COUNT(la)')
            ->andWhere($qb->expr()->gte('la.blocksLoginUntil', ':now'))
            ->andWhere($qb->expr()->eq('la.username', ':username'))
            ->getQuery()
            ->setParameters([
                'now' =>  new \DateTime('now'),
                'username' => $username,
            ])
            ->getSingleScalarResult() > 0 ? true : false
        ;
    }

    /**
     * Checks if an IP address tries more than 10 usernames
     * in the last 5 minutes.
     *
     * @param string $ipAddress
     * @param int $seconds
     * @param int $count
     *
     * @return bool
     */
    public function isIpAddressBlocked(string $ipAddress, int $seconds = 1200, int $count = 10): bool
    {
        $qb = $this->createQueryBuilder('la');
        $query = $qb->select('SUM(la.attemptCount)')
            ->andWhere($qb->expr()->gte('la.date', ':now'))
            ->andWhere($qb->expr()->eq('la.ipAddress', ':ipAddress'))
            ->getQuery()
            ->setParameters([
                'now' =>  (new \DateTime())->sub(new \DateInterval('PT' . $seconds . 'S')),
                'ipAddress' => $ipAddress,
            ])
        ;
        return $query->getSingleScalarResult() > $count ? true : false;
    }

    /**
     * @param string $ipAddress
     * @param string $username
     */
    public function findOrCreateOneByIpAddressAndUsername(string $ipAddress, string $username): LoginAttempt
    {
        $loginAttempt = $this->findOneBy([
            'ipAddress' => $ipAddress,
            'username' => $username,
        ]);
        if (null === $loginAttempt) {
            $loginAttempt = new LoginAttempt($ipAddress, $username);
            $this->_em->persist($loginAttempt);
        }

        return $loginAttempt;
    }

    /**
     * @param string $ipAddress
     * @param string $username
     */
    public function resetLoginAttempts(string $ipAddress, string $username): void
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->delete(LoginAttempt::class, 'la')
            ->andWhere($qb->expr()->eq('la.ipAddress', ':ipAddress'))
            ->andWhere($qb->expr()->eq('la.username', ':username'))
            ->getQuery()
            ->execute([
                'username' => $username,
                'ipAddress' => $ipAddress,
            ])
        ;
    }

    /**
     * @param string $ipAddress
     */
    public function purgeLoginAttempts(string $ipAddress): void
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->delete(LoginAttempt::class, 'la')
            ->andWhere($qb->expr()->eq('la.ipAddress', ':ipAddress'))
            ->getQuery()
            ->execute([
                'ipAddress' => $ipAddress,
            ])
        ;
    }

    /**
     * @param string $username
     */
    public function cleanLoginAttempts(): void
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->delete(LoginAttempt::class, 'la')
            ->andWhere($qb->expr()->lte('la.blocksLoginUntil', ':date'))
            ->getQuery()
            ->execute([
                'date' =>  (new \DateTime())->sub(new \DateInterval('P1D')),
            ])
        ;
    }
}
