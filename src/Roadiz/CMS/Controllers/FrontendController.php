<?php
/**
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
 * @file FrontendController.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\CMS\Controllers;

use Pimple\Container;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Handlers\NodesSourcesHandler;
use RZ\Roadiz\Core\Routing\NodeRouteHelper;
use RZ\Roadiz\Utils\Security\FirewallEntry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Security\Http\Firewall\AnonymousAuthenticationListener;

/**
 * Frontend controller to handle a page request.
 *
 * This class must be inherited in order to create a new theme.
 */
abstract class FrontendController extends AppController
{
    /**
     * {@inheritdoc}
     */
    protected static $themeName = 'Default theme';
    /**
     * {@inheritdoc}
     */
    protected static $themeAuthor = 'Ambroise Maupate';
    /**
     * {@inheritdoc}
     */
    protected static $themeCopyright = 'REZO ZERO';
    /**
     * {@inheritdoc}
     */
    protected static $themeDir = 'DefaultTheme';
    /**
     * {@inheritdoc}
     */
    protected static $backendTheme = false;

    /**
     * Put here your node which need a specific controller
     * instead of a node-type controller.
     *
     * @var array
     */
    protected static $specificNodesControllers = [
        'home',
    ];

    /**
     * @var Node
     */
    protected $node = null;
    /**
     * @var NodesSources
     */
    protected $nodeSource = null;
    /**
     * @var Translation
     */
    protected $translation = null;
    /**
     * @var Container
     */
    protected $themeContainer = null;

    /**
     * Default action for any node URL.
     *
     * @param Request $request
     * @param Node             $node
     * @param Translation      $translation
     *
     * @return Response
     */
    public function indexAction(
        Request $request,
        Node $node = null,
        Translation $translation = null
    ) {
        $this->get('stopwatch')->start('handleNodeController');
        $this->node = $node;
        $this->translation = $translation;

        //  Main node based routing method
        return $this->handle($request, $this->node, $this->translation);
    }

    /**
     * Default action for default URL (homepage).
     *
     * @param Request $request
     * @param string|null                              $_locale
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function homeAction(Request $request, $_locale = null)
    {
        /*
         * If you use a static route for Home page
         * we need to grab manually language.
         *
         * Get language from static route
         */
        $translation = $this->bindLocaleFromRoute($request, $_locale);

        /*
         * Grab home flagged node
         */
        $node = $this->getHome($translation);
        $this->prepareThemeAssignation($node, $translation);

        return $this->render('home.html.twig', $this->assignation);
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
     * @param Node        $node
     * @param Translation $translation
     */
    public function storeNodeAndTranslation(Node $node = null, Translation $translation = null)
    {
        $this->node = $node;
        $this->translation = $translation;
        $this->assignation['translation'] = $this->translation;
        $this->getRequest()->attributes->set('translation', $this->translation);

        if (null !== $this->node) {
            $this->getRequest()->attributes->set('node', $this->node);
            $this->nodeSource = $this->node->getNodeSources()->first();
            $this->assignation['node'] = $this->node;
            $this->assignation['nodeSource'] = $this->nodeSource;
        }

        $this->assignation['pageMeta'] = $this->getNodeSEO();
    }

    /**
     * Store current nodeSource and translation into controller.
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
     * @param NodesSources $nodeSource
     * @param Translation $translation
     */
    public function storeNodeSourceAndTranslation(NodesSources $nodeSource = null, Translation $translation = null)
    {
        $this->nodeSource = $nodeSource;

        if (null !== $this->nodeSource) {
            $this->node = $this->nodeSource->getNode();
            $this->translation = $this->nodeSource->getTranslation();

            $this->getRequest()->attributes->set('translation', $this->translation);
            $this->getRequest()->attributes->set('node', $this->node);

            $this->assignation['translation'] = $this->translation;
            $this->assignation['node'] = $this->node;
            $this->assignation['nodeSource'] = $this->nodeSource;
        } else {
            $this->translation = $translation;
            $this->assignation['translation'] = $this->translation;
            $this->getRequest()->attributes->set('translation', $this->translation);
        }

        $this->assignation['pageMeta'] = $this->getNodeSEO();
    }


    /**
     * Initialize controller with environment from an other controller
     * in order to avoid initializing same component again.
     *
     * @param array $baseAssignation
     * @param Container $themeContainer
     */
    public function __initFromOtherController(
        array &$baseAssignation = [],
        Container $themeContainer = null
    ) {
        $this->assignation = $baseAssignation;
        $this->themeContainer = $themeContainer;
    }

    /**
     * Handle node based routing, returns a Response object
     * for a node-based request.
     *
     * @param Request $request
     * @param Node $node
     * @param Translation $translation
     *
     * @return Response
     */
    protected function handle(
        Request $request,
        Node $node = null,
        Translation $translation = null
    ) {
        $this->get('stopwatch')->start('handleNodeController');

        if ($node !== null) {
            $nodeRouteHelper = new NodeRouteHelper(
                $node,
                $this->getTheme(),
                $this->get('kernel')->isPreview()
            );
            $controllerPath = $nodeRouteHelper->getController();
            $method = $nodeRouteHelper->getMethod();

            if (true !== $nodeRouteHelper->isViewable()) {
                $msg = "No front-end controller found for '" .
                $node->getNodeName() .
                "' node. You need to create a " . $controllerPath . ".";

                throw new ResourceNotFoundException($msg);
            }

            $ctrl = new $controllerPath();

            /*
             * Inject current Kernel to the matched Controller
             */
            if ($ctrl instanceof FrontendController) {
                $ctrl->setContainer($this->container);

                /*
                 * As we are creating an other controller
                 * we don't need to init again, so we pass the
                 * environment to the next level.
                 */
                $ctrl->__initFromOtherController(
                    $this->assignation,
                    $this->themeContainer
                );
            }
            $this->get('stopwatch')->stop('handleNodeController');
            return $ctrl->$method(
                $request,
                $node,
                $translation
            );
        }

        throw new ResourceNotFoundException("No front-end controller found");
    }

    /**
     * Add a default translation locale for static routes and
     * node SEO data.
     *
     * * [parent assignations…]
     * * **_default_locale**
     * * meta
     *     * siteName
     *     * siteCopyright
     *     * siteDescription
     */
    public function prepareBaseAssignation()
    {
        parent::prepareBaseAssignation();

        $translation = $this->get('defaultTranslation');

        $this->assignation['_default_locale'] = $translation->getLocale();
        $this->assignation['meta'] = [
            'siteName' => $this->get('settingsBag')->get('site_name'),
            'siteCopyright' => $this->get('settingsBag')->get('site_copyright'),
            'siteDescription' => $this->get('settingsBag')->get('seo_description'),
        ];

        return $this;
    }

    /**
     * Store basic informations for your theme from a Node object.
     *
     * @param Node        $node
     * @param Translation $translation
     *
     * @return void
     */
    protected function prepareThemeAssignation(Node $node = null, Translation $translation = null)
    {
        $this->container['stopwatch']->start('prepareThemeAssignation');
        $this->storeNodeAndTranslation($node, $translation);
        $this->assignation['home'] = $this->getHome($translation);
        /*
         * Use a DI container to delay API requests
         */
        $this->themeContainer = new Container();

        $this->container['stopwatch']->start('extendAssignation');
        $this->extendAssignation();
        $this->container['stopwatch']->stop('extendAssignation');
        $this->container['stopwatch']->stop('prepareThemeAssignation');
    }

    /**
     * Store basic informations for your theme from a NodesSources object.
     *
     * @param NodesSources $nodeSource
     * @param Translation $translation
     *
     * @return void
     */
    protected function prepareNodeSourceAssignation(NodesSources $nodeSource = null, Translation $translation = null)
    {
        $this->storeNodeSourceAndTranslation($nodeSource, $translation);
        $this->assignation['home'] = $this->getHome($translation);
        /*
         * Use a DI container to delay API requests
         */
        $this->themeContainer = new Container();

        $this->extendAssignation();
    }

    /**
     * Extends theme assignation with custom data.
     *
     * Override this method in your theme to add your own service
     * and data.
     */
    protected function extendAssignation()
    {
    }

    /**
     * Get SEO informations for current node.
     *
     * This method must return a 3-fields array with:
     *
     * * `title`
     * * `description`
     * * `keywords`
     *
     * @param NodesSources $fallbackNodeSource
     *
     * @return array
     */
    public function getNodeSEO(NodesSources $fallbackNodeSource = null)
    {
        if (null !== $this->nodeSource) {
            /** @var NodesSourcesHandler $nodesSourcesHandler */
            $nodesSourcesHandler = $this->get('factory.handler')->getHandler($this->nodeSource);
            return $nodesSourcesHandler->getSEO();
        }

        if (null !== $fallbackNodeSource) {
            /** @var NodesSourcesHandler $nodesSourcesHandler */
            $nodesSourcesHandler = $this->get('factory.handler')->getHandler($fallbackNodeSource);
            return $nodesSourcesHandler->getSEO();
        }

        return [
            'title' => '',
            'description' => '',
            'keywords' => '',
        ];
    }

    /**
     * Append objects to global container.
     *
     * Add a request matcher on frontend to make securityTokenStorage
     * available even when no user has logged in.
     *
     * @param Container $container
     */
    public static function setupDependencyInjection(Container $container)
    {
        parent::setupDependencyInjection($container);

        /**
         * You can override default frontend firewall
         * by overriding addDefaultFirewallEntry method
         *
         * @see FrontendController::addDefaultFirewallEntry
         */
        static::addDefaultFirewallEntry($container);
    }

    /**
     * Declare a default firewall for any routes and adds
     * an Anonymous token and context listener to fetch current
     * user information in front-end.
     *
     * Override this method to create a custom frontend security scheme
     * with FirewallEntry.
     *
     * @param Container $container
     * @see FirewallEntry
     */
    public static function addDefaultFirewallEntry(Container $container)
    {
        $firewallBasePattern = '^/';
        $firewallBasePath = '/';

        $firewallEntry = new FirewallEntry(
            $container,
            $firewallBasePattern,
            $firewallBasePath,
            null,
            null,
            null,
            'IS_AUTHENTICATED_ANONYMOUSLY'
        );
        $firewallEntry
            ->withSwitchUserListener()
            ->withAnonymousAuthenticationListener();

        $container['firewallMap']->add(
            $firewallEntry->getRequestMatcher(),
            $firewallEntry->getListeners(),
            $firewallEntry->getExceptionListener()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function maintenanceAction(Request $request)
    {
        $translation = $this->bindLocaleFromRoute($request, $request->getLocale());
        $this->prepareThemeAssignation(null, $translation);

        return new Response(
            $this->renderView('maintenance.html.twig', $this->assignation),
            Response::HTTP_SERVICE_UNAVAILABLE,
            ['content-type' => 'text/html']
        );
    }
}
