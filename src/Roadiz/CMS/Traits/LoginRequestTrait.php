<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Traits;

use Doctrine\Persistence\ObjectManager;
use Psr\Log\LoggerInterface;
use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\Core\Viewers\UserViewer;
use RZ\Roadiz\Random\TokenGenerator;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Trait LoginRequestTrait.
 *
 * This trait MUST be used in Controllers ONLY.
 *
 * @package RZ\Roadiz\CMS\Traits
 */
trait LoginRequestTrait
{
    /**
     * @param FormInterface         $form
     * @param ObjectManager         $entityManager
     * @param LoggerInterface       $logger
     * @param UrlGeneratorInterface $urlGenerator
     * @param string                $resetRoute
     *
     * @return bool TRUE if confirmation has been sent. FALSE if errors
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function sendConfirmationEmail(
        FormInterface $form,
        ObjectManager $entityManager,
        LoggerInterface $logger,
        UrlGeneratorInterface $urlGenerator,
        string $resetRoute = 'loginResetPage'
    ) {
        $email = $form->get('email')->getData();
        /** @var User $user */
        $user = $entityManager->getRepository(User::class)->findOneByEmail($email);

        if (null !== $user) {
            if (!$user->isPasswordRequestNonExpired(User::CONFIRMATION_TTL)) {
                try {
                    $tokenGenerator = new TokenGenerator($logger);
                    $user->setPasswordRequestedAt(new \DateTime());
                    $user->setConfirmationToken($tokenGenerator->generateToken());
                    $entityManager->flush();
                    /** @var UserViewer $userViewer */
                    $userViewer = $this->get('user.viewer');
                    $userViewer->setUser($user);
                    $userViewer->sendPasswordResetLink($urlGenerator, $resetRoute);
                    return true;
                } catch (\Exception $e) {
                    $user->setPasswordRequestedAt(null);
                    $user->setConfirmationToken(null);
                    $entityManager->flush();
                    $form->addError(new FormError($e->getMessage()));
                }
            } else {
                $form->addError(new FormError('a.confirmation.email.has.already.be.sent'));
            }
        }

        return false;
    }
}
