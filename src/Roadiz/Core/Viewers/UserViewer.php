<?php
declare(strict_types=1);

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
