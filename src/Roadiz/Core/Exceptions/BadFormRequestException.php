<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Exceptions;

/**
 * Exception raised when a form is sent without the right parameters.
 */
class BadFormRequestException extends \Exception
{
    protected $statusText;
    protected $fieldErrored;

    /**
     * @param string $message
     * @param int $code
     * @param string $statusText
     * @param string $fieldErrored
     */
    public function __construct($message = null, $code = 403, $statusText = 'danger', $fieldErrored = null)
    {
        parent::__construct($message, $code);

        $this->statusText = $statusText;
        $this->fieldErrored = $fieldErrored;
    }

    public function getStatusText()
    {
        return $this->statusText;
    }

    public function getFieldErrored()
    {
        return $this->fieldErrored;
    }
}
