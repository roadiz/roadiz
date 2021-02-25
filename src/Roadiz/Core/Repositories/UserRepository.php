<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Repositories;

/**
 * @package RZ\Roadiz\Core\Repositories
 * @extends EntityRepository<\RZ\Roadiz\Core\Entities\User>
 */
class UserRepository extends EntityRepository
{
    /**
     * @param string $username
     *
     * @return boolean
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function usernameExists($username)
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select($qb->expr()->count('u.username'))
            ->andWhere($qb->expr()->eq('u.username', ':username'))
            ->setParameter('username', $username)
            ->setCacheable(true);

        return (boolean) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param string $email
     *
     * @return boolean
     */
    public function emailExists($email)
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select($qb->expr()->count('u.email'))
            ->andWhere($qb->expr()->eq('u.email', ':email'))
            ->setParameter('email', $email)
            ->setCacheable(true);

        return (boolean) $qb->getQuery()->getSingleScalarResult();
    }
}
