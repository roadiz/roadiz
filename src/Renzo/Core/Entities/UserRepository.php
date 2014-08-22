<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file UserRepository.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Entities;

use RZ\Renzo\Core\Utils\EntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\AbstractEntity;
use RZ\Renzo\Core\Utils\StringHandler;
use RZ\Renzo\Core\Kernel;
/**
 * {@inheritdoc}
 */
class UserRepository extends EntityRepository
{
    /**
     * @param string $username
     *
     * @return array
     */
    public function usernameExists($username)
    {
        $query = $this->_em->createQuery('
            SELECT COUNT(u.username) FROM RZ\Renzo\Core\Entities\User u
            WHERE u.username = :username')
                        ->setParameter('username', $username);

        try {
            return (boolean) $query->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return false;
        }
    }

    /**
     * @param string $email
     *
     * @return array
     */
    public function emailExists($email)
    {
        $query = $this->_em->createQuery('
            SELECT COUNT(u.email) FROM RZ\Renzo\Core\Entities\User u
            WHERE u.email = :email')
                        ->setParameter('email', $email);

        try {
            return (boolean) $query->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return false;
        }
    }
}