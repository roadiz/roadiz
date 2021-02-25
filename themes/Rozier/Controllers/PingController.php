<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Themes\Rozier\RozierApp;

/**
 * @package Themes\Rozier\Controllers
 */
class PingController extends RozierApp
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response $response
     */
    public function indexAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_BACKEND_USER');
        return $this->renderJson(['Pong']);
    }
}
