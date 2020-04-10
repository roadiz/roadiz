<?php
/**
 * Copyright (c) 2017. Ambroise Maupate and Julien Blanchet
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
 * @file UserLifeCycleSubscriber.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

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
use RZ\Roadiz\Utils\EmailManager;
use RZ\Roadiz\Utils\MediaFinders\FacebookPictureFinder;
use RZ\Roadiz\Utils\Security\PasswordGenerator;
use RZ\Roadiz\Utils\Security\TokenGenerator;
use Swift_TransportException;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class UserLifeCycleSubscriber implements EventSubscriber
{
    /**
     * @var Container
     */
    private $container;

    /**
     * UserLifeCycleSubscriber constructor.
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
                    'loginResetPage'
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
