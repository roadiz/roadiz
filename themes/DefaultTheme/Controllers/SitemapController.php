<?php
declare(strict_types=1);

namespace Themes\DefaultTheme\Controllers;

use RZ\Roadiz\Core\Entities\NodeType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\DefaultTheme\DefaultThemeApp;

/**
 * @package Themes\DefaultTheme\Controllers
 */
class SitemapController extends DefaultThemeApp
{
    /**
     * @param Request $request
     * @param string  $_locale
     *
     * @return Response
     */
    public function sitemapAction(
        Request $request,
        $_locale = 'fr'
    ) {
        $this->prepareThemeAssignation(null, $this->bindLocaleFromRoute($request, $_locale));

        $nodeTypes = $this->get('em')
            ->getRepository(NodeType::class)
            ->findBy([
                'reachable' => true
            ]);

        /*
         * Add your own nodes grouped by their type.
         */
        $this->assignation['pages'] = $this->get('nodeSourceApi')
            ->getBy([
                'node.nodeType' => $nodeTypes,
                'node.visible' => true,
            ]);

        $response = new Response(
            trim($this->getTwig()->render('sitemap/sitemap.xml.twig', $this->assignation)),
            Response::HTTP_OK,
            ['content-type' => 'application/xml']
        );

        $this->makeResponseCachable($request, $response, 60);
        return $response;
    }
}
