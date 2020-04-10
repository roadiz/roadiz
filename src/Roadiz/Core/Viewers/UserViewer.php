<?php
declare(strict_types=1);
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
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
 * Description
 *
 * @file UserViewer.php
 * @author Ambroise Maupate
 */

namespace RZ\Roadiz\Core\Viewers;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Bags\Settings;
use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\Utils\EmailManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * UserViewer
 */
class UserViewer
{
    /** @var User|null  */
    protected $user;

    /** @var EntityManager */
    protected $entityManager;

    /** @var Settings */
    protected $settingsBag;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var EmailManager */
    protected $emailManager;

    /**
     * @param EntityManager $entityManager
     * @param Settings $settingsBag
     * @param TranslatorInterface $translator
     * @param EmailManager $emailManager
     * @internal param User $user
     */
    public function __construct(
        EntityManager $entityManager,
        Settings $settingsBag,
        TranslatorInterface $translator,
        EmailManager $emailManager
    ) {
        $this->entityManager = $entityManager;
        $this->settingsBag = $settingsBag;
        $this->translator = $translator;
        $this->emailManager = $emailManager;
    }

    /**
     * Send an email to reset user password.
     *
     * @param UrlGeneratorInterface $urlGenerator
     * @param string                $route
     *
     * @return bool
     * @throws \Exception
     */
    public function sendPasswordResetLink(
        UrlGeneratorInterface $urlGenerator,
        $route = 'loginResetPage'
    ) {
        $emailContact = $this->getContactEmail();
        $siteName = $this->getSiteName();

        $this->emailManager->setAssignation([
            'resetLink' => $urlGenerator->generate($route, [
                'token' => $this->user->getConfirmationToken(),
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            'user' => $this->user,
            'site' => $siteName,
            'mailContact' => $emailContact,
        ]);
        $this->emailManager->setEmailTemplate('users/reset_password_email.html.twig');
        $this->emailManager->setEmailPlainTextTemplate('users/reset_password_email.txt.twig');
        $this->emailManager->setSubject($this->translator->trans(
            'reset.password.request'
        ));
        $this->emailManager->setReceiver($this->user->getEmail());
        $this->emailManager->setSender([$emailContact => $siteName]);

        // Send the message
        return $this->emailManager->send();
    }

    /**
     * @return string
     */
    protected function getContactEmail()
    {
        $emailContact = $this->settingsBag->get('email_sender');
        if (empty($emailContact)) {
            $emailContact = "noreply@roadiz.io";
        }

        return $emailContact;
    }

    /**
     * @return string
     */
    protected function getSiteName()
    {
        $siteName = $this->settingsBag->get('site_name');
        if (empty($siteName)) {
            $siteName = "Unnamed site";
        }

        return $siteName;
    }

    /**
     * @return null|User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param null|User $user
     * @return UserViewer
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }
}
