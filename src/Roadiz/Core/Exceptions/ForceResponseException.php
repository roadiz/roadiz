<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Exceptions;

use Symfony\Component\HttpFoundation\Response;

/**
 * Exception raised when you want to force a given Response object.
 */
class ForceResponseException extends \Exception
{
    protected $response;

    public function __construct(Response $response)
    {
        parent::__construct('Forcing response…', 1);
        $this->response = $response;
    }

    /**
     * Gets the value of response.
     *
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Sets the value of response.
     *
     * @param Response $response the response
     *
     * @return self
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;

        return $this;
    }
}
