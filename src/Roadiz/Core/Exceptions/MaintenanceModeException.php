<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Exceptions;

use RZ\Roadiz\CMS\Controllers\AppController;

class MaintenanceModeException extends \Exception
{
    protected $controller;

    /**
     * @return AppController
     */
    public function getController()
    {
        return $this->controller;
    }

    protected $message = 'Website is currently under maintenance. We will be back shortly.';

    /**
     * @param AppController $controller
     * @param string $message
     * @param int $code
     */
    public function __construct(AppController $controller = null, $message = null, $code = 0)
    {
        if (null !== $message) {
            parent::__construct($message, $code);
        } else {
            parent::__construct($this->message, $code);
        }

        $this->controller = $controller;
    }
}
