<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Console\Helper;

use Swift_Mailer;
use Symfony\Component\Console\Helper\Helper;

class MailerHelper extends Helper
{
    protected Swift_Mailer $mailer;

    public function __construct(Swift_Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'mailer';
    }

    /**
     * Send the given Message like it would be sent in a mail client.
     *
     * All recipients (with the exception of Bcc) will be able to see the other
     * recipients this message was sent to.
     *
     * Recipient/sender data will be retrieved from the Message object.
     *
     * The return value is the number of recipients who were accepted for
     * delivery.
     *
     * @param \Swift_Message $message
     * @param array $failedRecipients An array of failures by-reference
     * @return int
     */
    public function send(\Swift_Message $message, &$failedRecipients = null)
    {
        return $this->mailer->send($message, $failedRecipients);
    }
}
