<?php
/**
 * Copyright © 2014, REZO ZERO
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
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file FrontendController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\CMS\Controllers;

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Utils\StringHandler;
use RZ\Roadiz\Core\Bags\SettingsBag;
use Pimple\Container;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestMatcher;

use Symfony\Component\Security\Http\Firewall;
use Symfony\Component\Security\Http\FirewallMap;
use Symfony\Component\Security\Http\Firewall\AnonymousAuthenticationListener;

/**
 * Frontend controller to handle a page request.
 *
 * This class must be inherited in order to create a new theme.
 */
class FrontendController extends AppController
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
     * Put here your node which need a specific controller
     * instead of a node-type controller.
     *
     * @var array
     */
    protected static $specificNodesControllers = array(
        'home',
    );

    protected $node = null;
    protected $translation = null;
    protected $themeContainer = null;

    /**
     * Make translation variable with the good localization
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param string                                   $_locale
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    protected function bindLocaleFromRoute(Request $request, $_locale = null)
    {
        $translation = null;

        /*
         * If you use a static route for Home page
         * we need to grab manually language.
         *
         * Get language from static route
         */
        if (null !== $_locale) {
            $request->setLocale($_locale);
            $translation = $this->getService('em')
                        ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                        ->findOneBy(
                            array(
                                'locale'=>$_locale
                            )
                        );
        } else {
            $translation = $this->getService('em')
                        ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                        ->findDefault();
            $request->setLocale($translation->getLocale());
        }
        return $translation;
    }

    /**
     * Default action for any node URL.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param RZ\Roadiz\Core\Entities\Node              $node
     * @param RZ\Roadiz\Core\Entities\Translation       $translation
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request, Node $node = null, Translation $translation = null)
    {
        Kernel::getService('stopwatch')->start('handleNodeController');
        $this->node = $node;
        $this->translation = $translation;

        //  Main node based routing method
        return $this->handle($request);
    }

    /**
     * Default action for default URL (homepage).
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param string|null                              $_locale
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function homeAction(Request $request, $_locale = null)
    {
        /*
         * If you use a static route for Home page
         * we need to grab manually language.
         *
         * Get language from static route
         */
        if (null !== $_locale) {
            $request->setLocale($_locale);
            $translation = $this->getService('em')
                        ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                        ->findOneBy(
                            array(
                                'locale'=>$_locale
                            )
                        );
        }

        /*
         * Grab home flagged node
         */
        $node = $this->getService('em')
                ->getRepository('RZ\Roadiz\Core\Entities\Node')
                ->findOneBy(
                    array('home'=>true),
                    null,
                    $translation,
                    $this->getSecurityContext()
                );

        $this->prepareThemeAssignation($node, $translation);

        return new Response(
            $this->getTwig()->render('home.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * Store current node and translation into controller.
     *
     * It makes following fields available into template assignation:
     *
     * * node
     * * nodeSource
     * * translation
     * * pageMeta
     *     * title
     *     * description
     *     * keywords
     *
     * @param RZ\Roadiz\Core\Entities\Node        $node
     * @param RZ\Roadiz\Core\Entities\Translation $translation
     */
    public function storeNodeAndTranslation(Node $node = null, Translation $translation = null)
    {
        $this->node = $node;
        $this->translation = $translation;

        $this->assignation['translation'] = $translation;

        if ($node !== null) {
            $this->assignation['node'] = $node;
            $this->assignation['nodeSource'] = $node->getNodeSources()->first();
        }

        $this->assignation['pageMeta'] = $this->getNodeSEO();
    }

    /**
     * Handle node based routing.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     * @throws Symfony\Component\Routing\Exception\ResourceNotFoundException If no front-end controller is available
     */
    protected function handle(Request $request)
    {
        Kernel::getService('stopwatch')->start('handleNodeController');
        $currentClass = get_class($this);
        $refl = new \ReflectionClass($currentClass);
        $namespace = $refl->getNamespaceName() . '\\Controllers';

        if ($this->getRequestedNode() !== null) {

            if (null !== $this->getSecurityContext() &&
                !$this->getSecurityContext()->isGranted(Role::ROLE_BACKEND_USER) &&
                !$this->getRequestedNode()->isPublished()) {
                /*
                 * Not allowed to see unpublished nodes
                 */
                return $this->throw404();
            }

            $nodeController = $namespace.'\\'.
                              StringHandler::classify($this->getRequestedNode()->getNodeName()).
                              'Controller';
            $nodeTypeController = $namespace.'\\'.
                                  StringHandler::classify($this->getRequestedNode()->getNodeType()->getName()).
                                  'Controller';

            if (in_array($this->getRequestedNode()->getNodeName(), static::$specificNodesControllers) &&
                class_exists($nodeController) &&
                method_exists($nodeController, 'indexAction')) {

                $ctrl = new $nodeController();

            } elseif (class_exists($nodeTypeController) &&
                method_exists($nodeTypeController, 'indexAction')) {

                $ctrl = new $nodeTypeController();

            } else {

                return $this->throw404(
                    "No front-end controller found for '".
                    $this->getRequestedNode()->getNodeName().
                    "' node. Need a ".$nodeController." or ".
                    $nodeTypeController." controller."
                );
            }

            /*
             * Inject current Kernel to the matched Controller
             */
            if ($ctrl instanceof AppController) {
                $ctrl->setKernel($this->getKernel());

                /*
                 * As we are creating an other controller
                 * we don't need to init again, so we pass the
                 * environment to the next level.
                 */
                $ctrl->__initFromOtherController(
                    $this->translator,
                    $this->assignation
                );
            }
            Kernel::getService('stopwatch')->stop('handleNodeController');
            return $ctrl->indexAction(
                $request,
                $this->getRequestedNode(),
                $this->getRequestedTranslation()
            );
        } else {

            return $this->throw404("No front-end controller found");
        }
    }


    /**
     * @return RZ\Roadiz\Core\Entities\Node
     */
    public function getRequestedNode()
    {
        return $this->node;
    }
    /**
     * @return RZ\Roadiz\Core\Entities\Translation
     */
    public function getRequestedTranslation()
    {
        return $this->translation;
    }

    /*
     * {@inheritdoc}
     *
     * Add a default translation locale for static routes.
     *
     * * **_default_locale**
     * * meta
     *     * siteName
     *     * siteCopyright
     *     * siteDescription
     */
    public function prepareBaseAssignation()
    {
        parent::prepareBaseAssignation();

        $translation = $this->getService('em')
                            ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                            ->findDefault();

        $this->assignation['_default_locale'] = $translation->getLocale();
        $this->assignation['meta'] = array(
            'siteName' =>        SettingsBag::get('site_name'),
            'siteCopyright' =>   SettingsBag::get('site_copyright'),
            'siteDescription' => SettingsBag::get('seo_description'),
            'analytics' =>       SettingsBag::get('universal_analytics_id')
        );

        return $this;
    }

    /**
     * Store basic informations for your theme.
     *
     * @param RZ\Roadiz\Core\Entities\Node        $node
     * @param RZ\Roadiz\Core\Entities\Translation $translation
     *
     * @return void
     */
    protected function prepareThemeAssignation(Node $node = null, Translation $translation = null)
    {
        $this->storeNodeAndTranslation($node, $translation);

        $this->assignation['home'] = $this->getService('em')
                                          ->getRepository('RZ\Roadiz\Core\Entities\Node')
                                          ->findHomeWithTranslation($translation);

        /*
         * Use a DI container to delay API requuests
         */
        $this->themeContainer = new Container();
    }

    /**
     * Get SEO informations for current node.
     *
     * @param NodesSources $fallbackNode
     *
     * @return array
     */
    public function getNodeSEO($fallbackNodeSource = null)
    {
        if (null !== $this->node) {
            $ns = $this->node->getNodeSources()->first();

            if (null !== $ns) {

                return array(
                    'title' => ($ns->getMetaTitle() != "") ?
                                        $ns->getMetaTitle() :
                                        $ns->getTitle().' – '.SettingsBag::get('site_name'),
                    'description' => ($ns->getMetaDescription() != "") ?
                                        $ns->getMetaDescription() :
                                        $ns->getTitle().', '.SettingsBag::get('seo_description'),
                    'keywords' => $ns->getMetaKeywords()
                );
            }
        }

        if (null !== $fallbackNodeSource) {
            return array(
                'title' => ($fallbackNodeSource->getMetaTitle() != "") ?
                                    $fallbackNodeSource->getMetaTitle() :
                                    $fallbackNodeSource->getTitle().' – '.SettingsBag::get('site_name'),
                'description' => ($fallbackNodeSource->getMetaDescription() != "") ?
                                    $fallbackNodeSource->getMetaDescription() :
                                    $fallbackNodeSource->getTitle().', '.SettingsBag::get('seo_description'),
                'keywords' => $fallbackNodeSource->getMetaKeywords()
            );
        }

        return array();
    }

    /**
     * Append objects to global container.
     *
     * @param Pimple\Container $container
     */
    public static function setupDependencyInjection(Container $container)
    {
        $container->extend('firewallMap', function (FirewallMap $map, Container $c) {
            /*
             * Prepare app firewall
             */
            $requestMatcher = new RequestMatcher('^/');

            $listeners = array(
                // manages the SecurityContext persistence through a session
                $c['contextListener'],
                // automatically adds a Token if none is already present.
                new AnonymousAuthenticationListener($c['securityContext'], '') // $key
            );

            /*
             * Inject a new firewall map element
             */
            $map->add($requestMatcher, $listeners, $c['firewallExceptionListener']);

            return $map;
        });
    }
}
