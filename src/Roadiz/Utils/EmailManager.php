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
 * @file EmailManager.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Utils;

use InlineStyle\InlineStyle;
use RZ\Roadiz\Core\Bags\SettingsBag;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Viewers\DocumentViewer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class EmailManager
 * @package RZ\Roadiz\Utils
 */
class EmailManager
{
    /** @var string|null */
    protected $subject = null;

    /** @var string|null */
    protected $emailTitle = null;

    /** @var string|null  */
    private $receiver = null;

    /** @var string|null  */
    private $sender = null;

    /** @var string|null  */
    private $origin = null;

    /** @var string  */
    protected $successMessage = 'email.successfully.sent';

    /** @var string  */
    protected $failMessage = 'email.has.errors';

    /** @var TranslatorInterface */
    protected $translator;

    /** @var \Twig_Environment */
    protected $templating;

    /** @var \Swift_Mailer */
    protected $mailer;

    /** @var string|null */
    protected $emailTemplate = null;

    /** @var string|null */
    protected $emailPlainTextTemplate = null;

    /** @var string */
    protected $emailStylesheet = '/src/Roadiz/CMS/Resources/css/transactionalStyles.css';

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var array
     */
    protected $assignation;

    /**
     * @var \Swift_Message
     */
    protected $message;


    /**
     * EmailManager constructor.
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param \Twig_Environment $templating
     * @param \Swift_Mailer $mailer
     */
    public function __construct(
        Request $request,
        TranslatorInterface $translator,
        \Twig_Environment $templating,
        \Swift_Mailer $mailer
    ) {
        $this->request = $request;
        $this->translator = $translator;
        $this->mailer = $mailer;
        $this->templating = $templating;
        $this->assignation = [];
        $this->message = null;
    }

    /**
     * @return string
     */
    public function renderHtmlEmailBody()
    {
        return $this->templating->render($this->getEmailTemplate(), $this->assignation);
    }

    /**
     * @return string
     */
    public function renderHtmlEmailBodyWithCss()
    {
        if (null !== $this->getEmailStylesheet()) {
            $htmldoc = new InlineStyle($this->renderHtmlEmailBody());
            $htmldoc->applyStylesheet(file_get_contents(
                ROADIZ_ROOT . $this->getEmailStylesheet()
            ));

            return $htmldoc->getHTML();
        }

        return $this->renderHtmlEmailBody();
    }

    /**
     * @return string
     */
    public function renderPlainTextEmailBody()
    {
        return $this->templating->render($this->getEmailPlainTextTemplate(), $this->assignation);
    }

    /**
     * Added mainColor and headerImageSrc assignation
     * to display email header.
     *
     * @return EmailManager
     */
    public function appendWebsiteIcon()
    {
        if (empty($this->assignation['mainColor'])) {
            $this->assignation['mainColor'] = SettingsBag::get('main_color');
        }

        if (empty($this->assignation['headerImageSrc'])) {
            $adminImage = SettingsBag::getDocument('admin_image');
            if (null !== $adminImage &&
                $adminImage instanceof Document) {
                $documentViewer = new DocumentViewer($adminImage);
                $this->assignation['headerImageSrc'] = $documentViewer->getDocumentUrlByArray([], true);
            }
        }

        return $this;
    }

    /**
     * @return \Swift_Message
     */
    public function createMessage()
    {
        $this->appendWebsiteIcon();

        $this->message = \Swift_Message::newInstance()
            // Give the message a subject
            ->setSubject($this->getSubject())
            ->setFrom($this->getOrigin())
            ->setTo($this->getReceiver())
            // Force using string and only one email
            ->setReturnPath($this->getSenderEmail());

        if (null !== $this->getEmailTemplate()) {
            $this->message->setBody($this->renderHtmlEmailBodyWithCss(), 'text/html');
        }
        if (null !== $this->getEmailPlainTextTemplate()) {
            $this->message->addPart($this->renderPlainTextEmailBody(), 'text/plain');
        }

        /*
         * Use sender email in ReplyTo: header only
         * to keep From: header with a know domain email.
         */
        if (null !== $this->getSender()) {
            $this->message->setReplyTo($this->getSender());
        }

        return $this->message;
    }

    /**
     * Send email.
     *
     * @return int
     * @throws \RuntimeException
     */
    public function send()
    {
        if (empty($this->assignation)) {
            throw new \RuntimeException("Can’t send a contact form without data.");
        }

        if (null === $this->message) {
            $this->message = $this->createMessage();
        }

        // Send the message
        return $this->mailer->send($this->message);
    }

    /**
     * @return null|string
     */
    public function getSubject()
    {
        return trim(strip_tags($this->subject));
    }

    /**
     * @param null|string $subject
     * @return EmailManager
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getEmailTitle()
    {
        return trim(strip_tags($this->emailTitle));
    }

    /**
     * @param null|string $emailTitle
     * @return EmailManager
     */
    public function setEmailTitle($emailTitle)
    {
        $this->emailTitle = $emailTitle;
        return $this;
    }

    /**
     * Message destination email(s).
     *
     * @return null|array|string
     */
    public function getReceiver()
    {
        return $this->receiver;
    }

    /**
     * Return only one email as string.
     *
     * @return null|string
     */
    public function getReceiverEmail()
    {
        if (is_array($this->receiver) && count($this->receiver) > 0) {
            $emails = array_keys($this->receiver);
            return $emails[0];
        }

        return $this->receiver;
    }

    /**
     * Sets the value of receiver.
     *
     * @param string|array $receiver the receiver
     *
     * @return EmailManager
     * @throws \Exception
     */
    public function setReceiver($receiver)
    {
        if (is_string($receiver)) {
            if (false === filter_var($receiver, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException("Sender must be a valid email address.", 1);
            }
        } elseif (is_array($receiver)) {
            foreach ($receiver as $email => $name) {
                if (false === filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new \InvalidArgumentException("Sender must be a valid email address.", 1);
                }
            }
        }

        $this->receiver = $receiver;

        return $this;
    }

    /**
     * Message virtual sender email.
     *
     * This email will be used as ReplyTo: and ReturnPath:
     *
     * @return null|string
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * Return only one email as string.
     *
     * @return null|string
     */
    public function getSenderEmail()
    {
        if (is_array($this->sender) && count($this->sender) > 0) {
            $emails = array_keys($this->sender);
            return $emails[0];
        }

        return $this->sender;
    }

    /**
     * Sets the value of sender.
     *
     * @param string|array $sender the sender
     * @return EmailManager
     * @throws \Exception
     */
    public function setSender($sender)
    {
        if (is_string($sender)) {
            if (false === filter_var($sender, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException("Sender must be a valid email address.", 1);
            }
        } elseif (is_array($sender)) {
            foreach ($sender as $email => $name) {
                if (false === filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new \InvalidArgumentException("Sender must be a valid email address.", 1);
                }
            }
        }

        $this->sender = $sender;

        return $this;
    }

    /**
     * @return string
     */
    public function getSuccessMessage()
    {
        return $this->successMessage;
    }

    /**
     * @param string $successMessage
     * @return EmailManager
     */
    public function setSuccessMessage($successMessage)
    {
        $this->successMessage = $successMessage;
        return $this;
    }

    /**
     * @return string
     */
    public function getFailMessage()
    {
        return $this->failMessage;
    }

    /**
     * @param string $failMessage
     * @return EmailManager
     */
    public function setFailMessage($failMessage)
    {
        $this->failMessage = $failMessage;
        return $this;
    }

    /**
     * @return TranslatorInterface
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * @param TranslatorInterface $translator
     * @return EmailManager
     */
    public function setTranslator($translator)
    {
        $this->translator = $translator;
        return $this;
    }

    /**
     * @return \Twig_Environment
     */
    public function getTemplating()
    {
        return $this->templating;
    }

    /**
     * @param \Twig_Environment $templating
     * @return EmailManager
     */
    public function setTemplating($templating)
    {
        $this->templating = $templating;
        return $this;
    }

    /**
     * @return \Swift_Mailer
     */
    public function getMailer()
    {
        return $this->mailer;
    }

    /**
     * @param \Swift_Mailer $mailer
     * @return EmailManager
     */
    public function setMailer($mailer)
    {
        $this->mailer = $mailer;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmailTemplate()
    {
        return $this->emailTemplate;
    }

    /**
     * @param string|null $emailTemplate
     * @return EmailManager
     */
    public function setEmailTemplate($emailTemplate = null)
    {
        $this->emailTemplate = $emailTemplate;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmailPlainTextTemplate()
    {
        return $this->emailPlainTextTemplate;
    }

    /**
     * @param string|null $emailPlainTextTemplate
     * @return EmailManager
     */
    public function setEmailPlainTextTemplate($emailPlainTextTemplate = null)
    {
        $this->emailPlainTextTemplate = $emailPlainTextTemplate;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmailStylesheet()
    {
        return $this->emailStylesheet;
    }

    /**
     * @param string|null $emailStylesheet
     * @return EmailManager
     */
    public function setEmailStylesheet($emailStylesheet = null)
    {
        $this->emailStylesheet = $emailStylesheet;
        return $this;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param Request $request
     * @return EmailManager
     */
    public function setRequest($request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Origin is the real From enveloppe.
     *
     * This must be an email address with a know
     * domain name to be validated on your SMTP server.
     *
     * @return null|string
     */
    public function getOrigin()
    {
        return (null !== $this->origin && $this->origin != "") ?
            ($this->origin) :
            (SettingsBag::get('email_sender'));
    }

    /**
     * @param string $origin
     * @return EmailManager
     */
    public function setOrigin($origin)
    {
        if (false === filter_var($origin, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Origin must be a valid email address.", 1);
        }

        $this->origin = $origin;
        return $this;
    }

    /**
     * @return array
     */
    public function getAssignation()
    {
        return $this->assignation;
    }

    /**
     * @param array $assignation
     * @return EmailManager
     */
    public function setAssignation($assignation)
    {
        $this->assignation = $assignation;
        return $this;
    }
}
