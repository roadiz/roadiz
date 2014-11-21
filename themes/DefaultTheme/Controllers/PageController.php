<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file PageController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace Themes\DefaultTheme\Controllers;

use Themes\DefaultTheme\DefaultThemeApp;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Utils\StringHandler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Frontend controller to handle Page node-type request.
 */
class PageController extends DefaultThemeApp
{

    /**
     * Default action for any Page node.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param RZ\Roadiz\Core\Entities\Node              $node
     * @param RZ\Roadiz\Core\Entities\Translation       $translation
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(
        Request $request,
        Node $node = null,
        Translation $translation = null
    ) {
        $this->prepareThemeAssignation($node, $translation);

        $this->getService('stopwatch')->start('twigRender');

        return new Response(
            $this->getTwig()->render('types/page.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }
}
