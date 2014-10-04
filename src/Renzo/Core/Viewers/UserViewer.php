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

namespace RZ\Renzo\Core\Viewers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\User;
use RZ\Renzo\Core\Bags\SettingsBag;

use Symfony\Bridge\Twig\Extension\RoutingExtension;
use Symfony\Bridge\Twig\Form\TwigRenderer;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 * UserViewer
 */
class UserViewer implements ViewableInterface
{
    protected $user = null;
    protected $twig = null;
    protected $translator = null;

    /**
     * @param RZ\Renzo\Core\Entities\User $user
     */
    public function __construct(User $user)
    {
        $this->initializeTwig()
             ->initializeTranslator();
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
     * Get twig cache folder for current Viewer
     *
     * @return string
     */
    public function getCacheDirectory()
    {
        return RENZO_ROOT.'/cache/Core/UserViewer/twig_cache';
    }

    /**
     * Create a Twig Environment instance
     *
     * @return  \Twig_Environment
     */
    public function initializeTwig()
    {
        $loader = new \Twig_Loader_Filesystem(array(
            RENZO_ROOT . '/src/Renzo/Core/Resources/views',
        ));
        $this->twig = new \Twig_Environment($loader, array(
            'cache' => $this->getCacheDirectory(),
            'debug' => Kernel::getInstance()->isDebug()
        ));

        // RoutingExtension
        $this->twig->addExtension(
            new RoutingExtension(Kernel::getService('urlGenerator'))
        );

        return $this;
    }

    /**
     * @return \Twig_Environment
     */
    public function getTwig()
    {
        return $this->twig;
    }

    /**
     * Create a translator instance and load theme messages
     *
     * src/Renzo/Core/Resources/translations/messages.{{lang}}.xlf
     *
     * @todo  [Cache] Need to write XLF catalog to PHP using \Symfony\Component\Translation\Writer\TranslationWriter
     *
     * @return Symfony\Component\Translation\Translator
     */
    public function initializeTranslator()
    {
        $lang = Kernel::getInstance()->getRequest()->getLocale();
        $msgPath = RENZO_ROOT.'/src/Renzo/Core/Resources/translations/messages.'.$lang.'.xlf';

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
            RENZO_ROOT.'/src/Renzo/Core/Resources/translations/messages.'.$lang.'.xlf',
            $lang
        );
        // ajoutez le TranslationExtension (nous donnant les filtres trans et transChoice)
        $this->twig->addExtension(new TranslationExtension($this->translator));

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
            'site' => SettingsBag::get('site_name')
        );
        $emailBody = $this->getTwig()->render('users/newUser_email.html.twig', $assignation);


        // Create the message
        $message = \Swift_Message::newInstance()
            // Give the message a subject
            ->setSubject($this->getTranslator()->trans(
                'welcome.user.email.%site%',
                array('%site%'=>SettingsBag::get('email_sender_name'))
            ))
            // Set the From address with an associative array
            ->setFrom(array(SettingsBag::get('email_sender') => SettingsBag::get('email_sender_name')))
            // Set the To addresses with an associative array
            ->setTo(array($this->user->getEmail()))
            // Give it a body
            ->setBody($emailBody, 'text/html');

        // Create the Transport
        $transport = \Swift_MailTransport::newInstance();
        $mailer = \Swift_Mailer::newInstance($transport);
        // Send the message

        return $mailer->send($message);
    }
}
