<?php

/*
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

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\Core\Bags\SettingsBag;
use Symfony\Bridge\Twig\Extension\TranslationExtension;

use \InlineStyle\InlineStyle;

/**
 * UserViewer
 */
class UserViewer implements ViewableInterface
{
    protected $user = null;
    protected $twig = null;

    /**
     * @param RZ\Roadiz\Core\Entities\User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;

        Kernel::getService('twig.environment')->addExtension(new TranslationExtension(Kernel::getService('translator')));
        Kernel::getService('twig.environment')->addExtension(new \Twig_Extensions_Extension_Intl());
    }

    /**
     * @return Symfony\Component\Translation\Translator
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
     * Send an email with credentials details to user
     *
     * @return void
     */
    public function sendSignInConfirmation()
    {
        $emailContact = SettingsBag::get('email_sender');

        if (empty($emailContact)) {
            $emailContact = "noreply@roadiz.io";
        }

        $siteName = SettingsBag::get('site_name');

        if (empty($siteName)) {
            $siteName = "Unnamed site";
        }

        $assignation = [
            'user' => $this->user,
            'site' => $siteName,
            'mailContact' => $emailContact,
        ];
        $emailBody = $this->getTwig()->render('users/newUser_email.html.twig', $assignation);

        /*
         * inline CSS
         */
        $htmldoc = new InlineStyle($emailBody);
        $htmldoc->applyStylesheet(file_get_contents(
            ROADIZ_ROOT."/src/Roadiz/CMS/Resources/css/transactionalStyles.css"
        ));

        // Create the message
        $message = \Swift_Message::newInstance()
            // Give the message a subject
            ->setSubject($this->getTranslator()->trans(
                'welcome.user.email.%site%',
                ['%site%'=>$siteName]
            ))
            // Set the From address with an associative array
            ->setFrom([$emailContact => $siteName])
            // Set the To addresses with an associative array
            ->setTo([$this->user->getEmail()])
            // Give it a body
            ->setBody($htmldoc->getHTML(), 'text/html');

        // Create the Transport
        $transport = \Swift_MailTransport::newInstance();
        $mailer = \Swift_Mailer::newInstance($transport);
        // Send the message

        return $mailer->send($message);
    }
}
