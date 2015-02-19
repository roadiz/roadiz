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
use RZ\Roadiz\Core\Bags\SettingsBag;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Exceptions\NoTranslationAvailableException;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\SecurityContext;
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

    protected $node = null;
    protected $translation = null;
    protected $themeContainer = null;

    /**
     * Make translation variable with the good localization.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param string                                   $_locale
     *
     * @return Symfony\Component\HttpFoundation\Response
     * @throws RZ\Roadiz\Core\Exceptions\NoTranslationAvailableException
     */
    protected function bindLocaleFromRoute(Request $request, $_locale = null)
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
                                    [
                                        'locale' => $_locale,
                                        'available' => true,
                                    ]
                                );
            if ($translation === null) {
                throw new NoTranslationAvailableException();
            }
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
                                    [
                                        'locale' => $_locale,
                                    ]
                                );
        } else {
            $translation = $this->getService('em')
                                ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                                ->findDefault();
        }

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
     * Get controller class path for a given node.
     *
     * @param RZ\Roadiz\Core\Entities\Node $node
     *
     * @return string
     */
    public function getControllerForNode(Node $node)
    {
        $currentClass = get_class($this);
        $refl = new \ReflectionClass($currentClass);
        $namespace = $refl->getNamespaceName() . '\\Controllers';

        /*
         * Determine if we look for a node-type named controller or
         * a node-named controller.
         */
        if (in_array($this->getRequestedNode()->getNodeName(), static::$specificNodesControllers)) {
            return $namespace . '\\' .
            StringHandler::classify($this->getRequestedNode()->getNodeName()) .
            'Controller';
        } else {
            return $namespace . '\\' .
            StringHandler::classify($this->getRequestedNode()->getNodeType()->getName()) .
            'Controller';
        }
    }

    /**
     * Return a 404 Response or TRUE if node is viewable.
     *
     * @param  RZ\Roadiz\Core\Entities\Node $node
     * @param  Symfony\Component\Security\Core\SecurityContext|null $securityContext
     *
     * @return boolean|Symfony\Component\HttpFoundation\Response
     */
    public function validateAccessForNodeWithStatus(Node $node, SecurityContext $securityContext = null)
    {
        if (null !== $securityContext &&
            !$securityContext->isGranted(Role::ROLE_BACKEND_USER) &&
            !$node->isPublished()) {
            /*
             * Not allowed to see unpublished nodes
             */
            return $this->throw404();
        } elseif (null !== $securityContext &&
            $securityContext->isGranted(Role::ROLE_BACKEND_USER) &&
            $node->getStatus() > Node::PUBLISHED) {
            /*
             * Not allowed to see deleted and archived nodes
             * even for Admins
             */
            return $this->throw404();
        } else {
            return true;
        }
    }

    /**
     * Handle node based routing, returns a Response object
     * for a node-based request.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\Routing\Exception\ResourceNotFoundException If no front-end controller is available
     */
    protected function handle(Request $request)
    {
        Kernel::getService('stopwatch')->start('handleNodeController');

        if ($this->getRequestedNode() !== null) {
            if (true !== $resp = $this->validateAccessForNodeWithStatus(
                $this->getRequestedNode(),
                $this->getSecurityContext()
            )) {
                return $resp;
            }

            /*
             * Determine if we look for a node-type named controller or
             * a node-named controller.
             */
            $controllerPath = $this->getControllerForNode($this->getRequestedNode());

            if (class_exists($controllerPath) &&
                method_exists($controllerPath, 'indexAction')) {
                $ctrl = new $controllerPath();
            } else {
                return $this->throw404(
                    "No front-end controller found for '" .
                    $this->getRequestedNode()->getNodeName() .
                    "' node. You need to create a " . $controllerPath . "."
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

        $translation = $this->getService('em')
                            ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                            ->findDefault();

        $this->assignation['_default_locale'] = $translation->getLocale();
        $this->assignation['meta'] = [
            'siteName' => SettingsBag::get('site_name'),
            'siteCopyright' => SettingsBag::get('site_copyright'),
            'siteDescription' => SettingsBag::get('seo_description'),
        ];

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
        $this->assignation['home'] = $this->getHome($translation);
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
                return [
                    'title' => ($ns->getMetaTitle() != "") ?
                    $ns->getMetaTitle() :
                    $ns->getTitle() . ' – ' . SettingsBag::get('site_name'),
                    'description' => ($ns->getMetaDescription() != "") ?
                    $ns->getMetaDescription() :
                    $ns->getTitle() . ', ' . SettingsBag::get('seo_description'),
                    'keywords' => $ns->getMetaKeywords(),
                ];
            }
        }

        if (null !== $fallbackNodeSource) {
            return [
                'title' => ($fallbackNodeSource->getMetaTitle() != "") ?
                $fallbackNodeSource->getMetaTitle() :
                $fallbackNodeSource->getTitle() . ' – ' . SettingsBag::get('site_name'),
                'description' => ($fallbackNodeSource->getMetaDescription() != "") ?
                $fallbackNodeSource->getMetaDescription() :
                $fallbackNodeSource->getTitle() . ', ' . SettingsBag::get('seo_description'),
                'keywords' => $fallbackNodeSource->getMetaKeywords(),
            ];
        }

        return [];
    }

    /**
     * Append objects to global container.
     *
     * Add a request matcher on frontend to make securityContext
     * available even when no user has logged in.
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

            $listeners = [
                // manages the SecurityContext persistence through a session
                $c['contextListener'],
                // automatically adds a Token if none is already present.
                new AnonymousAuthenticationListener($c['securityContext'], ''), // $key
                $c["switchUser"],
            ];
            /*
             * Inject a new firewall map element
             */
            $map->add($requestMatcher, $listeners, $c['firewallExceptionListener']);

            return $map;
        });

        /*
         * Enable frontend theme to extends backoffice and using FrontendTheme twig templates.
         */
        $container->extend('twig.loaderFileSystem', function (\Twig_Loader_Filesystem $loader, $c) {
            $loader->addPath(static::getViewsFolder());

            return $loader;
        });
    }
}
