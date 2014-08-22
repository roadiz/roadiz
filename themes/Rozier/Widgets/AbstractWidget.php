<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file AbstractWidget.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Widgets;

use RZ\Renzo\CMS\Controller\AppController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
/**
 * A widget always has to be created and called from a valid AppController
 * in order to get Twig renderer engine, security context and request context.
 */
abstract class AbstractWidget
{
    protected $controller;
    protected $request;

    /**
     * @return Symfony\Component\HttpFoundation\Request
     */
    protected function getRequest()
    {
        return $this->request;
    }
    /**
     * @return RZ\Renzo\CMS\Controller\AppController
     */
    protected function getController()
    {
        return $this->controller;
    }

    /**
     * @param Symfony\Component\HttpFoundation\Request $request           Current kernel request
     * @param RZ\Renzo\CMS\Controller\AppController    $refereeController Referee controller to get Twig, security context from.
     */
    public function __construct(Request $request, $refereeController)
    {
        if ($refereeController == null) {
            throw new \RuntimeException("Referee AppController cannot be null to instanciate a new Widget", 1);
        }
        if ($request == null) {
            throw new \RuntimeException("Request cannot be null to instanciate a new Widget", 1);
        }

        $this->controller = $refereeController;
        $this->request = $request;
    }
}