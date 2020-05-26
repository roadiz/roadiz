<?php
declare(strict_types=1);

namespace Themes\Rozier\Widgets;

use RZ\Roadiz\CMS\Controllers\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * A widget always has to be created and called from a valid AppController
 * in order to get Twig renderer engine, security context and request context.
 */
abstract class AbstractWidget
{
    protected $controller;
    protected $request;

    /**
     * @return Request
     */
    protected function getRequest()
    {
        return $this->request;
    }
    /**
     * @return Controller
     */
    protected function getController()
    {
        return $this->controller;
    }

    /**
     * @param Request    $request           Current kernel request
     * @param Controller $refereeController Referee controller to get Twig, security context from.
     */
    public function __construct(Request $request, Controller $refereeController)
    {
        if ($refereeController === null) {
            throw new \RuntimeException("Referee AppController cannot be null to instantiate a new Widget", 1);
        }
        if ($request === null) {
            throw new \RuntimeException("Request cannot be null to instantiate a new Widget", 1);
        }

        $this->controller = $refereeController;
        $this->request = $request;
    }
}
