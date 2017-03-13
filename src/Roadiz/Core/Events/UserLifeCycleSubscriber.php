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
use RZ\Roadiz\Core\Bags\SettingsBag;
use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\Utils\EmailManager;
use RZ\Roadiz\Utils\MediaFinders\FacebookPictureFinder;
use RZ\Roadiz\Utils\Security\PasswordGenerator;
use RZ\Roadiz\Utils\Security\SaltGenerator;

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
            if ($event->hasChangedField('enabled') && true === $event->getNewValue('enabled')) {
                $this->container->offsetGet('logger')->info('User was enabled.', ['username' => $user->getUsername()]);
                $userEvent = new FilterUserEvent($user);
                $this->container->offsetGet('dispatcher')->dispatch(UserEvents::USER_ENABLED, $userEvent);
            }

            if ($event->hasChangedField('enabled') && false === $event->getNewValue('enabled')) {
                $this->container->offsetGet('logger')->info('User was disabled.', ['username' => $user->getUsername()]);
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
        }
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
     */
    public function postPersist(LifecycleEventArgs $event)
    {
        $user = $event->getEntity();
        if ($user instanceof User ) {
            if ($user->willSendCreationConfirmationEmail()) {
                $this->sendSignInConfirmation($user);
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
            $saltGenerator = new SaltGenerator();
            $user->setSalt($saltGenerator->generateSalt());

            /*
             * If no plain password is present, we must generate one
             */
            if ($user->getPlainPassword() == '') {
                $passwordGenerator = new PasswordGenerator();
                $user->setPlainPassword($passwordGenerator->generatePassword(12));
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
     * @return bool
     */
    private function sendSignInConfirmation(User $user)
    {
        $emailContact = SettingsBag::get('email_sender');
        if (empty($emailContact)) {
            $emailContact = "noreply@roadiz.io";
        }

        $siteName = SettingsBag::get('site_name');
        if (empty($siteName)) {
            $siteName = "Unnamed site";
        }

        $emailManager = new EmailManager(
            $this->container->offsetGet('request'),
            $this->container->offsetGet('translator'),
            $this->container->offsetGet('twig.environment'),
            $this->container->offsetGet('mailer')
        );
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
