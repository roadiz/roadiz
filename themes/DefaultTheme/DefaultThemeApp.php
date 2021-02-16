<?php
declare(strict_types=1);

namespace Themes\DefaultTheme;

use RZ\Roadiz\CMS\Controllers\FrontendController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\DefaultTheme\Services\NodeServiceProvider;

/**
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

        $this->assignation['themeServices'] = $this->themeContainer;
        $this->assignation['head']['themeName'] = static::$themeName;
        /*
         * Get social networks url from Roadiz parameters.
         */
        $socials = ['Twitter', 'Facebook', 'Instagram', 'YouTube', 'LinkedIn', 'GooglePlus', 'Pinterest'];
        $this->assignation['head']['socials'] = [];
        foreach ($socials as $social) {
            $setting = $this->get('settingsBag')->get(strtolower($social) . '_url');
            if ($setting) {
                $this->assignation['head']['socials'][strtolower($social)] = [
                    'name'  => $social,
                    'slug'  => strtolower($social),
                    'url'   => $setting,
                ];
            }
        }
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
        /** @var Request $request */
        $request = $this->get('requestStack')->getCurrentRequest();
        $translation = $this->bindLocaleFromRoute(
            $request,
            $request->getLocale()
        );
        $this->prepareThemeAssignation(null, $translation);
        $this->assignation['nodeName'] = 'error-404';
        $this->assignation['nodeTypeName'] = 'error404';
        $this->assignation['errorMessage'] = $message;
        $this->assignation['title'] = $this->get('translator')->trans('error404.title');
        $this->assignation['content'] = $this->get('translator')->trans('error404.message');

        $this->get('stopwatch')->start('twigRender');
        return new Response(
            $this->renderView('@DefaultTheme/pages/404.html.twig', $this->assignation),
            Response::HTTP_NOT_FOUND,
            ['content-type' => 'text/html']
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
}
