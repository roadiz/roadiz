<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Traits;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\Form\FormInterface;

trait LoginResetTrait
{
    /**
     * @param EntityManager $entityManager
     * @param string $token
     * @return null|User
     */
    public function getUserByToken(EntityManager $entityManager, string $token)
    {
        /** @var User $user */
        return $entityManager->getRepository(User::class)
            ->findOneByConfirmationToken($token);
    }

    /**
     * @param FormInterface $form
     * @param User          $user
     * @param EntityManager $entityManager
     *
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateUserPassword(FormInterface $form, User $user, EntityManager $entityManager)
    {
        $user->setConfirmationToken(null);
        $user->setPasswordRequestedAt(null);
        $user->setPlainPassword($form->get('plainPassword')->getData());
        /*
         * If user was forced to update its credentials,
         * we remove expiration.
         */
        if (!$user->isCredentialsNonExpired()) {
            if ($user->getCredentialsExpired() === true) {
                $user->setCredentialsExpired(false);
            }
            if (null !== $user->getCredentialsExpiresAt()) {
                $user->setCredentialsExpiresAt(null);
            }
        }
        $entityManager->flush();

        return true;
    }
}
