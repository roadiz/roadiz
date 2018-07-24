<?php
/**
 * Copyright © 2016, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file DefaultThemeApp.php
 * @author Ambroise Maupate
 */
namespace Themes\DefaultTheme;

use Pimple\Container;
use RZ\Roadiz\CMS\Controllers\FrontendController;
use RZ\Roadiz\Core\Events\FilterSolariumNodeSourceEvent;
use RZ\Roadiz\Core\Events\NodesSourcesEvents;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\DefaultTheme\Services\AssetsServiceProvider;
use Themes\DefaultTheme\Services\NodeServiceProvider;

/**
 * Class DefaultThemeApp
 * @package Themes\DefaultTheme
 */
class DefaultThemeApp extends FrontendController
{
    protected static $themeName = 'Default theme';
    protected static $themeAuthor = 'Ambroise Maupate';
    protected static $themeCopyright = 'REZO ZERO';
    protected static $themeDir = 'DefaultTheme';
    protected static $backendTheme = false;

    /**
     * @param Request $request
     * @param null $_locale
     * @return Response
     */
    public function homeAction(
        Request $request,
        $_locale = null
    ) {
        /*
         * If you use a static route for Home page
         * we need to grab manually language.
         *
         * Get language from static route
         */
        $translation = $this->bindLocaleFromRoute($request, $_locale);
        $home = $this->getHome($translation);

        /*
         * Use home page node-type to render it.
         */
        return $this->handle($request, $home, $translation);
    }

    /**
     * {@inheritdoc}
     */
    protected function extendAssignation()
    {
        parent::extendAssignation();

        /*
         * Register services
         */
        $this->themeContainer->register(new NodeServiceProvider($this->getContainer(), $this->translation));
        $this->themeContainer->register(new AssetsServiceProvider());

        $this->assignation['themeServices'] = $this->themeContainer;

        $this->assignation['head']['facebookUrl'] = $this->get('settingsBag')->get('facebook_url');
        $this->assignation['head']['pinterest_url'] = $this->get('settingsBag')->get('pinterest_url');
        $this->assignation['head']['facebookClientId'] = $this->get('settingsBag')->get('facebook_client_id');
        $this->assignation['head']['instagramUrl'] = $this->get('settingsBag')->get('instagram_url');
        $this->assignation['head']['twitterUrl'] = $this->get('settingsBag')->get('twitter_url');
        $this->assignation['head']['googleplusUrl'] = $this->get('settingsBag')->get('googleplus_url');
        $this->assignation['head']['googleClientId'] = $this->get('settingsBag')->get('google_client_id');
        $this->assignation['head']['twitterAccount'] = $this->get('settingsBag')->get('twitter_account');
        $this->assignation['head']['mapsStyle'] = $this->get('settingsBag')->get('maps_style');
        $this->assignation['head']['themeName'] = static::$themeName;
    }

    /**
     * Return a Response with default backend 404 error page.
     *
     * @param string $message Additional message to describe 404 error.
     *
     * @return Response
     */
    public function throw404($message = '')
    {
        $translation = $this->bindLocaleFromRoute(
            $this->get('requestStack')->getCurrentRequest(),
            $this->get('requestStack')->getCurrentRequest()->getLocale()
        );
        $this->prepareThemeAssignation(null, $translation);
        $this->get('logger')->warn($message);

        $this->assignation['nodeName'] = 'error-404';
        $this->assignation['nodeTypeName'] = 'error404';
        $this->assignation['errorMessage'] = $message;
        $this->assignation['title'] = $this->get('translator')->trans('error404.title');
        $this->assignation['content'] = $this->get('translator')->trans('error404.message');


        $this->get('stopwatch')->start('twigRender');
        return new Response(
            $this->renderView('@DefaultTheme/pages/404.html.twig', $this->assignation),
            Response::HTTP_NOT_FOUND,
            array('content-type' => 'text/html')
        );
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function maintenanceAction(Request $request)
    {
        $translation = $this->bindLocaleFromRoute($request, $request->getLocale());
        $this->prepareThemeAssignation(null, $translation);

        $this->assignation['nodeName'] = 'maintenance' ;
        $this->assignation['nodeTypeName'] = 'maintenance';
        $this->assignation['title'] = $this->get('translator')->trans('website.is.under.maintenance');
        $this->assignation['content'] = $this->get('translator')->trans('website.is.under.maintenance.we.will.be.back.soon');

        $this->get('stopwatch')->start('twigRender');
        return new Response(
            $this->renderView('@DefaultTheme/pages/maintenance.html.twig', $this->assignation),
            Response::HTTP_SERVICE_UNAVAILABLE,
            ['content-type' => 'text/html']
        );
    }

    /**
     * @param Container $container
     */
    public static function setupDependencyInjection(Container $container)
    {
        parent::setupDependencyInjection($container);

        $container->extend('backoffice.entries', function (array $entries, $c) {
            /*
             * Add a test entry in your Backoffice
             * Remove this in your theme if you don’t
             * want to extend Back-office
             */
            $entries['test'] = [
                'name' => 'test',
                'path' => $c['urlGenerator']->generate('adminTestPage'),
                'icon' => 'uk-icon-cube',
                'roles' => null,
                'subentries' => null,
            ];

            return $entries;
        });

        /*
         * Example:
         * Alter Solr indexing with custom data.
         */
        /** @var EventDispatcher $dispatcher */
        $dispatcher = $container['dispatcher'];
        $dispatcher->addListener(NodesSourcesEvents::NODE_SOURCE_INDEXING, function (FilterSolariumNodeSourceEvent $event) {
            $assoc = $event->getAssociations();
            $assoc['defaulttheme_txt'] = 'This is injected by Default theme during indexing.';
            $event->setAssociations($assoc);
        });
    }
}
