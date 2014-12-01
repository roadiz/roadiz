<?php

/*
 * Copyright REZO ZERO 2014
 *
 * Description
 *
 * @file UserViewer.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */

namespace RZ\Roadiz\Core\Viewers;

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\Core\Bags\SettingsBag;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Bridge\Twig\Extension\TranslationExtension;

use \InlineStyle\InlineStyle;

/**
 * UserViewer
 */
class UserViewer implements ViewableInterface
{
    protected $user = null;
    protected $twig = null;
    protected $translator = null;

    /**
     * @param RZ\Roadiz\Core\Entities\User $user
     */
    public function __construct(User $user)
    {
        $this->initializeTranslator();
        $this->user = $user;
    }

    /**
     * @return Symfony\Component\Translation\Translator
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * @return \Twig_Environment
     */
    public function getTwig()
    {
        return Kernel::getService('twig.environment');
    }

    /**
     * Create a translator instance and load theme messages
     *
     * src/Roadiz/CMS/Resources/translations/messages.{{lang}}.xlf
     *
     * @todo  [Cache] Need to write XLF catalog to PHP using \Symfony\Component\Translation\Writer\TranslationWriter
     *
     * @return Symfony\Component\Translation\Translator
     */
    public function initializeTranslator()
    {
        $lang = Kernel::getInstance()->getRequest()->getLocale();
        $msgPath = RENZO_ROOT.'/src/Roadiz/CMS/Resources/translations/messages.'.$lang.'.xlf';

        /*
         * fallback to english, if message catalog absent
         */
        if (!file_exists($msgPath)) {
            $lang = 'en';
        }
        // instancier un objet de la classe Translator
        $this->translator = new Translator($lang);
        // charger, en quelque sorte, des traductions dans ce translator
        $this->translator->addLoader('xlf', new XliffFileLoader());
        $this->translator->addResource(
            'xlf',
            RENZO_ROOT.'/src/Roadiz/CMS/Resources/translations/messages.'.$lang.'.xlf',
            $lang
        );
        // ajoutez le TranslationExtension (nous donnant les filtres trans et transChoice)
        Kernel::getService('twig.environment')->addExtension(new TranslationExtension($this->translator));

        return $this;
    }

    /**
     * Send an email with credentials details to user
     *
     * @return void
     */
    public function sendSignInConfirmation()
    {
        $assignation = array(
            'user' => $this->user,
            'site' => SettingsBag::get('site_name'),
            'mailContact' => SettingsBag::get('email_sender'),
        );
        $emailBody = $this->getTwig()->render('users/newUser_email.html.twig', $assignation);

        /*
         * inline CSS
         */
        $htmldoc = new InlineStyle($emailBody);
        $htmldoc->applyStylesheet(file_get_contents(
            RENZO_ROOT."/src/Roadiz/CMS/Resources/css/transactionalStyles.css"
        ));

        // Create the message
        $message = \Swift_Message::newInstance()
            // Give the message a subject
            ->setSubject($this->getTranslator()->trans(
                'welcome.user.email.%site%',
                array('%site%'=>SettingsBag::get('site_name'))
            ))
            // Set the From address with an associative array
            ->setFrom(array(SettingsBag::get('email_sender') => SettingsBag::get('site_name')))
            // Set the To addresses with an associative array
            ->setTo(array($this->user->getEmail()))
            // Give it a body
            ->setBody($htmldoc->getHTML(), 'text/html');

        // Create the Transport
        $transport = \Swift_MailTransport::newInstance();
        $mailer = \Swift_Mailer::newInstance($transport);
        // Send the message

        return $mailer->send($message);
    }
}
