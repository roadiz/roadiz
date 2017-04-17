<?php
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

use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\EmailManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * UserViewer
 */
class UserViewer implements ViewableInterface
{
    protected $user = null;
    protected $twig = null;

    /**
     * @param \RZ\Roadiz\Core\Entities\User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @return \Symfony\Component\Translation\Translator
     */
    public function getTranslator()
    {
        return Kernel::getService('translator');
    }

    /**
     * @return \Twig_Environment
     */
    public function getTwig()
    {
        return Kernel::getService('twig.environment');
    }

    /**
     * Send an email with credentials details to user.
     *
     * @return boolean
     * @deprecated Sign in confirmation is already sent by user lifecycle events.
     */
    public function sendSignInConfirmation()
    {
        $emailContact = Kernel::getService('settingsBag')->get('email_sender');
        if (empty($emailContact)) {
            $emailContact = "noreply@roadiz.io";
        }

        $siteName = Kernel::getService('settingsBag')->get('site_name');
        if (empty($siteName)) {
            $siteName = "Unnamed site";
        }

        /** @var EmailManager $emailManager */
        $emailManager = Kernel::getService('emailManager');
        $emailManager->setAssignation([
            'user' => $this->user,
            'site' => $siteName,
            'mailContact' => $emailContact,
        ]);
        $emailManager->setEmailTemplate('users/newUser_email.html.twig');
        $emailManager->setEmailPlainTextTemplate('users/newUser_email.txt.twig');
        $emailManager->setSubject(Kernel::getService('translator')->trans(
            'welcome.user.email.%site%',
            ['%site%' => $siteName]
        ));
        $emailManager->setReceiver($this->user->getEmail());
        $emailManager->setSender([$emailContact => $siteName]);

        // Send the message
        return $emailManager->send();
    }

    /**
     * Send an email to reset user password.
     *
     * @param UrlGeneratorInterface $urlGenerator
     *
     * @return boolean
     */
    public function sendPasswordResetLink(UrlGeneratorInterface $urlGenerator)
    {
        $emailContact = Kernel::getService('settingsBag')->get('email_sender');
        if (empty($emailContact)) {
            $emailContact = "noreply@roadiz.io";
        }

        $siteName = Kernel::getService('settingsBag')->get('site_name');
        if (empty($siteName)) {
            $siteName = "Unnamed site";
        }

        /** @var EmailManager $emailManager */
        $emailManager = Kernel::getService('emailManager');
        $emailManager->setAssignation([
            'resetLink' => $urlGenerator->generate('loginResetPage', [
                'token' => $this->user->getConfirmationToken(),
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            'user' => $this->user,
            'site' => $siteName,
            'mailContact' => $emailContact,
        ]);
        $emailManager->setEmailTemplate('users/reset_password_email.html.twig');
        $emailManager->setEmailPlainTextTemplate('users/reset_password_email.txt.twig');
        $emailManager->setSubject(Kernel::getService('translator')->trans(
            'reset.password.request'
        ));
        $emailManager->setReceiver($this->user->getEmail());
        $emailManager->setSender([$emailContact => $siteName]);

        // Send the message
        return $emailManager->send();
    }
}
