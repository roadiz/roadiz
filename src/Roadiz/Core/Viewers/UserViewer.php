<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Viewers;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RZ\Roadiz\Core\Bags\Settings;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\Utils\EmailManager;
use Swift_TransportException;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;

class UserViewer
{
    protected Settings $settingsBag;
    protected TranslatorInterface $translator;
    protected EmailManager $emailManager;
    protected LoggerInterface $logger;
    protected ?User $user = null;

    /**
     * @param Settings $settingsBag
     * @param TranslatorInterface $translator
     * @param EmailManager $emailManager
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        Settings $settingsBag,
        TranslatorInterface $translator,
        EmailManager $emailManager,
        ?LoggerInterface $logger = null
    ) {
        $this->settingsBag = $settingsBag;
        $this->translator = $translator;
        $this->emailManager = $emailManager;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Send email to reset user password.
     *
     * @param UrlGeneratorInterface $urlGenerator
     * @param string|NodesSources $route
     * @param string $htmlTemplate
     * @param string $txtTemplate
     *
     * @return bool
     * @throws \Exception
     */
    public function sendPasswordResetLink(
        UrlGeneratorInterface $urlGenerator,
        $route = 'loginResetPage',
        string $htmlTemplate = 'users/reset_password_email.html.twig',
        string $txtTemplate = 'users/reset_password_email.txt.twig'
    ): bool {
        $emailContact = $this->getContactEmail();
        $siteName = $this->getSiteName();

        if (is_string($route)) {
            $resetLink = $urlGenerator->generate(
                $route,
                [
                    'token' => $this->user->getConfirmationToken(),
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        } else {
            $resetLink = $urlGenerator->generate(
                RouteObjectInterface::OBJECT_BASED_ROUTE_NAME,
                [
                    RouteObjectInterface::ROUTE_OBJECT => $route,
                    'token' => $this->user->getConfirmationToken(),
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        }
        $this->emailManager->setAssignation([
            'resetLink' => $resetLink,
            'user' => $this->user,
            'site' => $siteName,
            'mailContact' => $emailContact,
        ]);
        $this->emailManager->setEmailTemplate($htmlTemplate);
        $this->emailManager->setEmailPlainTextTemplate($txtTemplate);
        $this->emailManager->setSubject($this->translator->trans(
            'reset.password.request'
        ));
        $this->emailManager->setReceiver($this->user->getEmail());
        $this->emailManager->setSender([$emailContact => $siteName]);

        try {
            // Send the message
            return $this->emailManager->send() > 0;
        } catch (Swift_TransportException $e) {
            // Silent error not to prevent user creation if mailer is not configured
            $this->logger->error('Unable to send password reset link', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * @return string
     */
    protected function getContactEmail(): string
    {
        $emailContact = $this->settingsBag->get('email_sender') ?? '';
        if (empty($emailContact)) {
            $emailContact = "noreply@roadiz.io";
        }

        return $emailContact;
    }

    /**
     * @return string
     */
    protected function getSiteName(): string
    {
        $siteName = $this->settingsBag->get('site_name') ?? '';
        if (empty($siteName)) {
            $siteName = "Unnamed site";
        }

        return $siteName;
    }

    /**
     * @return null|User
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param null|User $user
     * @return UserViewer
     */
    public function setUser(?User $user)
    {
        $this->user = $user;
        return $this;
    }
}
