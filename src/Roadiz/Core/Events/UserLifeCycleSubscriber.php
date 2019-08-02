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
use RZ\Roadiz\Utils\EmailManager;
use RZ\Roadiz\Utils\MediaFinders\FacebookPictureFinder;
use RZ\Roadiz\Utils\Security\PasswordGenerator;
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
                $userEvent = new FilterUserEvent($user);
                $this->container->offsetGet('dispatcher')->dispatch(UserEvents::USER_ENABLED, $userEvent);
            }

            if ($event->hasChangedField('enabled') &&
                false === $event->getNewValue('enabled')) {
                $userEvent = new FilterUserEvent($user);
                $this->container->offsetGet('dispatcher')->dispatch(UserEvents::USER_DISABLED, $userEvent);
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
                $userEvent = new FilterUserEvent($user);
                $this->container->offsetGet('dispatcher')->dispatch(UserEvents::USER_PASSWORD_CHANGED, $userEvent);
            }
        }
    }

    /**
     * @param User $user
     * @param $plainPassword
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
            $userEvent = new FilterUserEvent($user);
            $this->container->offsetGet('dispatcher')->dispatch(UserEvents::USER_UPDATED, $userEvent);
        }
    }

    /**
     * @param LifecycleEventArgs $event
     */
    public function postRemove(LifecycleEventArgs $event)
    {
        $user = $event->getEntity();
        if ($user instanceof User) {
            $userEvent = new FilterUserEvent($user);
            $this->container->offsetGet('dispatcher')->dispatch(UserEvents::USER_DELETED, $userEvent);
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
            if ($user->willSendCreationConfirmationEmail()) {
                try {
                    $this->sendSignUpConfirmation($user);
                } catch (Swift_TransportException $e) {
                    $this->container->offsetGet('logger')->emergency('Cannot send user sign-up confirmation by email', [
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
            $userEvent = new FilterUserEvent($user);
            $this->container->offsetGet('dispatcher')->dispatch(UserEvents::USER_CREATED, $userEvent);
        }
    }

    /**
     * @param LifecycleEventArgs $event
     */
    public function prePersist(LifecycleEventArgs $event)
    {
        $user = $event->getEntity();
        if ($user instanceof User) {
            /*
             * If no plain password is present, we must generate one
             */
            if (null === $user->getPlainPassword() ||
                $user->getPlainPassword() === '') {
                $passwordGenerator = new PasswordGenerator();
                $plainPassword = $passwordGenerator->generatePassword(12);
                $user->setPlainPassword($plainPassword);
                $this->setPassword($user, $plainPassword);
            } else {
                $this->setPassword($user, $user->getPlainPassword());
            }

            /*
             * Force a gravatar image if not defined
             */
            if ($user->getPictureUrl() == '') {
                $user->setPictureUrl($user->getGravatarUrl());
            }
        }
    }

    /**
     * Send an email with credentials details to user.
     *
     * @param User $user
     *
     * @return bool
     * @throws \Exception
     */
    private function sendSignUpConfirmation(User $user)
    {
        $emailContact = $this->container['settingsBag']->get('email_sender');
        if (empty($emailContact)) {
            $emailContact = "noreply@roadiz.io";
        }

        $siteName = $this->container['settingsBag']->get('site_name');
        if (empty($siteName)) {
            $siteName = "Unnamed site";
        }

        /** @var EmailManager $emailManager */
        $emailManager = $this->container['emailManager'];
        $emailManager->setAssignation([
            'user' => $user,
            'site' => $siteName,
            'mailContact' => $emailContact,
        ]);
        $emailManager->setEmailTemplate('users/newUser_email.html.twig');
        $emailManager->setEmailPlainTextTemplate('users/newUser_email.txt.twig');
        $emailManager->setSubject($this->container->offsetGet('translator')->trans(
            'welcome.user.email.%site%',
            ['%site%' => $siteName]
        ));
        $emailManager->setReceiver($user->getEmail());
        $emailManager->setSender([$emailContact => $siteName]);

        // Send the message
        return $emailManager->send();
    }
}
