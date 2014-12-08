<?php
/*
 * Copyright REZO ZERO 2014
 *
 * Description
 *
 * @file DefaultThemeApp.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */

namespace Themes\DefaultTheme;

use RZ\Roadiz\CMS\Controllers\FrontendController;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Translation;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
* DefaultThemeApp class
*/
class DefaultThemeApp extends FrontendController
{
    protected static $themeName =      'Default theme';
    protected static $themeAuthor =    'Ambroise Maupate';
    protected static $themeCopyright = 'REZO ZERO';
    protected static $themeDir =       'DefaultTheme';
    protected static $backendTheme =    false;

    /**
     * {@inheritdoc}
     */
    protected static $specificNodesControllers = array(
        // Put here your nodes which need a specific controller
        // instead of a node-type controller
    );

    /**
     * {@inheritdoc}
     */
    public function homeAction(
        Request $request,
        $_locale = null
    ) {
        /*
         * We need to manually grab language.
         *
         * Get language from static route
         */
        $translation = $this->bindLocaleFromRoute($request, $_locale);

        $home = $this->getService('em')
                     ->getRepository('RZ\Roadiz\Core\Entities\Node')
                     ->findHomeWithTranslation($translation);

        $this->prepareThemeAssignation($home, $translation);

        /*
         * Render Homepage manually
         */
        return new Response(
            $this->getTwig()->render('home.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Node        $node
     * @param RZ\Roadiz\Core\Entities\Translation $translation
     *
     * @return void
     */
    protected function prepareThemeAssignation(Node $node = null, Translation $translation = null)
    {
        parent::prepareThemeAssignation($node, $translation);

        $this->themeContainer['imageFormats'] = function ($c)
        {
            $array = array();

            /*
             * Common image format for pages headers
             */
            $array['headerImage'] = array(
                'width'=>1024,
                'crop'=>'1024x200'
            );
            $array['thumbnail'] = array(
                "width"=>600,
                "crop"=>"16x9",
                "controls"=>true,
                "embed"=>true
            );

            return $array;
        };

        $this->themeContainer['navigation'] = function ($c)
        {
            return $this->assignMainNavigation();
        };

        /*
         * Use Grunt to generate unique asset files for CSS and JS
         */
        $this->themeContainer['grunt'] = function ($c) {
            return include(dirname(__FILE__).'/static/public/config/assets.config.php');
        };

        $this->assignation['home'] = $this->getService('em')
                                          ->getRepository('RZ\Roadiz\Core\Entities\Node')
                                          ->findHomeWithTranslation($translation);

        $this->assignation['themeServices'] = $this->themeContainer;
    }

    /**
     * @return RZ\Roadiz\Core\Entities\Node
     */
    protected function assignMainNavigation()
    {
        if ($this->translation === null) {
            $this->translation = $this->getService('em')
                ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                ->findOneBy(
                    array('defaultTranslation'=>true)
                );
        }
        $parent = $this->getService('em')
                       ->getRepository('RZ\Roadiz\Core\Entities\Node')
                       ->findHomeWithTranslation($this->translation);

        if ($parent !== null) {
            return $this->getService('nodeApi')
                        ->getBy(
                            array('parent' => $parent)
                        );
        }

        return null;
    }

    /**
     * Return a Response with default backend 404 error page.
     *
     * @param string $message Additionnal message to describe 404 error.
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function throw404($message = "")
    {
        $this->prepareThemeAssignation(null, null);

        $this->assignation['errorMessage'] = $message;

        return new Response(
            $this->getTwig()->render('404.html.twig', $this->assignation),
            Response::HTTP_NOT_FOUND,
            array('content-type' => 'text/html')
        );
    }
}
