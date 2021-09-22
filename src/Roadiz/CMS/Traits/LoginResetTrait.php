<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Traits;

use Doctrine\Persistence\ObjectManager;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\Form\FormInterface;

trait LoginResetTrait
{
    /**
     * @param ObjectManager $entityManager
     * @param string $token
     * @return null|User
     */
    public function getUserByToken(ObjectManager $entityManager, string $token)
    {
        /** @var User $user */
        return $entityManager->getRepository(User::class)
            ->findOneByConfirmationToken($token);
    }

    /**
     * @param FormInterface $form
     * @param User          $user
     * @param ObjectManager $entityManager
     *
     * @return bool
     */
    public function updateUserPassword(FormInterface $form, User $user, ObjectManager $entityManager)
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
