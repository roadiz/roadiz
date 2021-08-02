<?php
declare(strict_types=1);

namespace RZ\Roadiz\Webhook\Exception;

use Throwable;

final class TooManyWebhookTriggeredException extends \RuntimeException
{
    private ?\DateTimeImmutable $doNotTriggerBefore;

    public function __construct(?\DateTimeImmutable $doNotTriggerBefore = null, $message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->doNotTriggerBefore = $doNotTriggerBefore;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getDoNotTriggerBefore(): \DateTimeImmutable
    {
        return $this->doNotTriggerBefore ?? \DateTimeImmutable::createFromMutable((new \DateTime())->add(new \DateInterval('PT30S')));
    }
}
