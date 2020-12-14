<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Pimple\Container;
use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\Core\Events\User\UserCreatedEvent;
use RZ\Roadiz\Core\Events\User\UserDeletedEvent;
use RZ\Roadiz\Core\Events\User\UserDisabledEvent;
use RZ\Roadiz\Core\Events\User\UserEnabledEvent;
use RZ\Roadiz\Core\Events\User\UserPasswordChangedEvent;
use RZ\Roadiz\Core\Events\User\UserUpdatedEvent;
use RZ\Roadiz\Core\Viewers\UserViewer;
use RZ\Roadiz\Utils\MediaFinders\FacebookPictureFinder;
use RZ\Roadiz\Utils\Security\TokenGenerator;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class UserLifeCycleSubscriber implements EventSubscriber
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [
            Events::preUpdate,
            Events::prePersist,
            Events::postPersist,
            Events::postUpdate,
            Events::postRemove,
        ];
    }

    /**
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(PreUpdateEventArgs $event)
    {
        $user = $event->getEntity();
        if ($user instanceof User) {
            if ($event->hasChangedField('enabled') &&
                true === $event->getNewValue('enabled')) {
                $userEvent = new UserEnabledEvent($user);
                $this->container->offsetGet('dispatcher')->dispatch($userEvent);
            }

            if ($event->hasChangedField('enabled') &&
                false === $event->getNewValue('enabled')) {
                $userEvent = new UserDisabledEvent($user);
                $this->container->offsetGet('dispatcher')->dispatch($userEvent);
            }

            if ($event->hasChangedField('facebookName')) {
                if ('' != $event->getNewValue('facebookName')) {
                    try {
                        $facebook = new FacebookPictureFinder($user->getFacebookName());
                        $url = $facebook->getPictureUrl();
                        $user->setPictureUrl($url);
                    } catch (\Exception $e) {
                        $user->setFacebookName('');
                        $user->setPictureUrl($user->getGravatarUrl());
                    }
                } else {
                    $user->setPictureUrl($user->getGravatarUrl());
                }
            }
            /*
             * Encode user password
             */
            if ($event->hasChangedField('password') &&
                null !== $user->getPlainPassword() &&
                '' !== $user->getPlainPassword()) {
                $this->setPassword($user, $user->getPlainPassword());
                $userEvent = new UserPasswordChangedEvent($user);
                $this->container->offsetGet('dispatcher')->dispatch($userEvent);
            }
        }
    }

    /**
     * @param User $user
     * @param string $plainPassword
     */
    protected function setPassword(User $user, $plainPassword)
    {
        /** @var EncoderFactoryInterface $encoderFactory */
        $encoderFactory = $this->container->offsetGet('userEncoderFactory');
        $encoder = $encoderFactory->getEncoder($user);
        $encodedPassword = $encoder->encodePassword(
            $plainPassword,
            $user->getSalt()
        );
        $user->setPassword($encodedPassword);
    }

    /**
     * @param LifecycleEventArgs $event
     */
    public function postUpdate(LifecycleEventArgs $event)
    {
        $user = $event->getEntity();
        if ($user instanceof User) {
            $userEvent = new UserUpdatedEvent($user);
            $this->container->offsetGet('dispatcher')->dispatch($userEvent);
        }
    }

    /**
     * @param LifecycleEventArgs $event
     */
    public function postRemove(LifecycleEventArgs $event)
    {
        $user = $event->getEntity();
        if ($user instanceof User) {
            $userEvent = new UserDeletedEvent($user);
            $this->container->offsetGet('dispatcher')->dispatch($userEvent);
        }
    }

    /**
     * @param LifecycleEventArgs $event
     *
     * @throws \Exception
     */
    public function postPersist(LifecycleEventArgs $event)
    {
        $user = $event->getEntity();
        if ($user instanceof User) {
            $userEvent = new UserCreatedEvent($user);
            $this->container->offsetGet('dispatcher')->dispatch($userEvent);
        }
    }

    /**
     * @param LifecycleEventArgs $event
     *
     * @throws \Exception
     */
    public function prePersist(LifecycleEventArgs $event)
    {
        $user = $event->getEntity();
        if ($user instanceof User) {
            if ($user->willSendCreationConfirmationEmail() &&
                (null === $user->getPlainPassword() ||
                $user->getPlainPassword() === '')) {
                /*
                 * Do not generate password for new users
                 * just send them a password reset link.
                 */
                $tokenGenerator = new TokenGenerator($this->container['logger']);
                $user->setCredentialsExpired(true);
                $user->setPasswordRequestedAt(new \DateTime());
                $user->setConfirmationToken($tokenGenerator->generateToken());
                /** @var UserViewer $userViewer */
                $userViewer = $this->container['user.viewer'];
                $userViewer->setUser($user);
                $userViewer->sendPasswordResetLink(
                    $this->container['urlGenerator'],
                    'loginResetPage',
                    'users/welcome_user_email.html.twig',
                    'users/welcome_user_email.txt.twig'
                );
            } else {
                $this->setPassword($user, $user->getPlainPassword());
            }

            /*
             * Force a Gravatar image if not defined
             */
            if ($user->getPictureUrl() == '') {
                $user->setPictureUrl($user->getGravatarUrl());
            }
        }
    }
}
