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
    /**
     * {@inheritdoc}
     */
    protected static $themeName =      'Default theme';
    /**
     * {@inheritdoc}
     */
    protected static $themeAuthor =    'Ambroise Maupate';
    /**
     * {@inheritdoc}
     */
    protected static $themeCopyright = 'REZO ZERO';
    /**
     * {@inheritdoc}
     */
    protected static $themeDir =       'DefaultTheme';
    /**
     * {@inheritdoc}
     */
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
         * If you use a static route for Home page
         * we need to grab manually language.
         *
         * Get language from static route
         */
        $translation = $this->bindLocaleFromRoute($request, $_locale);

        $this->prepareThemeAssignation(null, $translation);

        /*
         * First choice, render Homepage as any other nodes
         */
        //return $this->handle($request);

        /*
         * Second choice, render Homepage manually
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
        $this->storeNodeAndTranslation($node, $translation);
        $this->assignation['navigation'] = $this->assignMainNavigation();

        $this->assignation['home'] = $this->getService('em')
                                          ->getRepository('RZ\Roadiz\Core\Entities\Node')
                                          ->findHomeWithTranslation($translation);

        /*
         * Common image format for pages headers
         */
        $this->assignation['headerImageFilter'] = array(
            'width'=>1024,
            'crop'=>'1024x200'
        );
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
}
