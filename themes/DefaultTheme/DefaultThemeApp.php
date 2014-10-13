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

use RZ\Renzo\CMS\Controllers\FrontendController;
use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Utils\StringHandler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

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

        $node = $this->getService('em')
                ->getRepository('RZ\Renzo\Core\Entities\Node')
                ->findOneBy(
                    array('home'=>true),
                    null,
                    $translation,
                    $this->getSecurityContext()
                );

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
     * @param RZ\Renzo\Core\Entities\Node        $node
     * @param RZ\Renzo\Core\Entities\Translation $translation
     *
     * @return void
     */
    protected function prepareThemeAssignation(Node $node = null, Translation $translation = null)
    {
        $this->storeNodeAndTranslation($node, $translation);
        $this->assignation['navigation'] = $this->assignMainNavigation();

        $this->assignation['home'] = $this->getService('em')
                                          ->getRepository('RZ\Renzo\Core\Entities\Node')
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
     * @return RZ\Renzo\Core\Entities\Node
     */
    protected function assignMainNavigation()
    {
        if ($this->translation === null) {
            $this->translation = $this->getService('em')
                ->getRepository('RZ\Renzo\Core\Entities\Translation')
                ->findOneBy(
                    array('defaultTranslation'=>true)
                );
        }
        $parent = $this->getService('em')
                       ->getRepository('RZ\Renzo\Core\Entities\Node')
                       ->findHomeWithTranslation($this->translation);

        if ($parent !== null) {
            return $this->getService('em')
                ->getRepository('RZ\Renzo\Core\Entities\Node')
                ->findByParentWithTranslation(
                    $this->translation,
                    $parent,
                    $this->getSecurityContext()
                );
        }

        return null;
    }
}
