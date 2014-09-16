<?php
/*
 * Copyright REZO ZERO 2014
 *
 * Description
 *
 * @file DefaultApp.php
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
* DefaultApp class
*/
class DefaultApp extends FrontendController
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
        'home',
        // Put here your nodes which need a specific controller
        // instead of a node-type controller
    );

    /**
     * Default action for default URL (homepage).
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param RZ\Renzo\Core\Entities\Node              $node
     * @param RZ\Renzo\Core\Entities\Translation       $translation
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function homeAction(Request $request, Node $node = null, Translation $translation = null)
    {
        if ($node === null) {
            $node = Kernel::getInstance()->em()
                    ->getRepository('RZ\Renzo\Core\Entities\Node')
                    ->findOneBy(array('home'=>true));
        }
        $this->prepareThemeAssignation($node, $translation);

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
        $parent = Kernel::getInstance()->em()
            ->getRepository('RZ\Renzo\Core\Entities\Node')
            ->findOneBy(array('home'=>true));

        if ($this->translation === null) {
            $this->translation = Kernel::getInstance()->em()
                ->getRepository('RZ\Renzo\Core\Entities\Translation')
                ->findOneBy(array('defaultTranslation'=>true));
        }
        if ($parent !== null) {
            return Kernel::getInstance()->em()
                ->getRepository('RZ\Renzo\Core\Entities\Node')
                ->findByParentWithTranslation($this->translation, $parent);
        }

        return null;
    }
}
