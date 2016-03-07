<?php
/*
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
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
 * Description
 *
 * @file DefaultThemeApp.php
 * @author Ambroise Maupate
 */

namespace Themes\DefaultTheme;

use Pimple\Container;
use RZ\Roadiz\CMS\Controllers\FrontendController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use \RZ\Roadiz\Core\Exceptions\NoTranslationAvailableException;

/**
 * DefaultThemeApp class
 */
class DefaultThemeApp extends FrontendController
{
    const USE_GRUNT = false;

    protected static $themeName = 'Default theme';
    protected static $themeAuthor = 'Ambroise Maupate';
    protected static $themeCopyright = 'REZO ZERO';
    protected static $themeDir = 'DefaultTheme';
    protected static $backendTheme = false;

    protected static $specificNodesControllers = [
        // Put here your nodes which need a specific controller
        // instead of a node-type controller
    ];

    public function homeAction(
        Request $request,
        $_locale = null
    ) {
        /*
         * You must catch NoTranslationAvailableException if
         * user visit a non-available translation.
         */
        try {
            $translation = $this->bindLocaleFromRoute($request, $_locale);
            $home = $this->getHome($translation);

            return $this->handle($request, $home, $translation);
        } catch (NoTranslationAvailableException $e) {
            return $this->throw404();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function extendAssignation()
    {
        parent::extendAssignation();

        $this->themeContainer['imageFormats'] = function ($c) {
            $array = [];

            /*
             * Common image format for pages headers
             */
            $array['headerImage'] = [
                'width' => 1600,
                'noProcess' => true,
            ];
            $array['thumbnail'] = [
                "fit" => "200x200",
                "controls" => true,
                "embed" => true,
            ];

            return $array;
        };

        $this->themeContainer['navigation'] = function ($c) {
            return $this->assignMainNavigation();
        };
        $this->themeContainer['basicBlockType'] = function ($c) {
            return $this->getService('nodeTypeApi')
                        ->getOneBy([
                            'name' => 'BasicBlock'
                        ]);
        };
        $this->themeContainer['pageType'] = function ($c) {
            return $this->getService('nodeTypeApi')
                        ->getOneBy([
                            'name' => 'Page'
                        ]);
        };

        $this->themeContainer['useGrunt'] = function ($c) {
            return static::USE_GRUNT;
        };

        /*
         * Use Grunt to generate unique asset files for CSS and JS
         */
        $this->themeContainer['grunt'] = function ($c) {
            return include dirname(__FILE__) . '/static/public/config/assets.config.php';
        };

        $this->assignation['home'] = $this->getHome($this->translation);
        $this->assignation['themeServices'] = $this->themeContainer;
        // Get session messages
        $this->assignation['session']['messages'] = $this->getService('session')->getFlashBag()->all();
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
                     ['defaultTranslation' => true]
                 );
        }
        $parent = $this->getHome($this->translation);

        if ($parent !== null) {
            $pageNodeType = $this->getService('em')
                ->getRepository('RZ\Roadiz\Core\Entities\NodeType')
                ->findOneByName('Page');
            return $this->getService('nodeApi')
                        ->getBy(
                            [
                                'parent' => $parent,
                                'translation' => $this->translation,
                                'nodeType' => $pageNodeType,
                            ],
                            [
                                'position' => 'ASC',
                            ]
                        );
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function throw404($message = "")
    {
        $this->prepareThemeAssignation(null, null);
        $this->getService('logger')->error($message);
        $this->assignation['errorMessage'] = $message;

        return new Response(
            $this->getTwig()->render('@DefaultTheme/404.html.twig', $this->assignation),
            Response::HTTP_NOT_FOUND,
            ['content-type' => 'text/html']
        );
    }

    /**
     * {@inheritdoc}
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
    }
}
