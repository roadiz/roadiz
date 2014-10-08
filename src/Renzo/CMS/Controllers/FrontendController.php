<?php
/**
 * Copyright REZO ZERO 2014
 *
 *
 * @file FrontendController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\CMS\Controllers;


use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Log\Logger;
use RZ\Renzo\Core\Entities\Role;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Utils\StringHandler;
use RZ\Renzo\Core\Handlers\UserProvider;
use RZ\Renzo\Core\Handlers\UserHandler;
use Pimple\Container;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestMatcher;

use Symfony\Component\HttpKernel\HttpKernelInterface;

use RZ\Renzo\Core\Authentification\AuthenticationSuccessHandler;
use RZ\Renzo\Core\Authorization\AccessDeniedHandler;

use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Firewall;
use Symfony\Component\Security\Http\FirewallMap;
use Symfony\Component\Security\Http\Firewall\ExceptionListener;
use Symfony\Component\Security\Http\Firewall\UsernamePasswordFormAuthenticationListener;
use Symfony\Component\Security\Http\Firewall\ContextListener;
use Symfony\Component\Security\Http\Firewall\LogoutListener;
use Symfony\Component\Security\Http\Firewall\AccessListener;
use Symfony\Component\Security\Http\Firewall\AnonymousAuthenticationListener;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategy;
use Symfony\Component\Security\Http\AccessMap;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;
use Symfony\Component\Security\Http\Logout\DefaultLogoutSuccessHandler;
use Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserChecker;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\Authorization\Voter\RoleVoter;

use Symfony\Component\EventDispatcher\EventDispatcher;

use Symfony\Component\Routing\Exception\ResourceNotFoundException;

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
                        ->getRepository('RZ\Renzo\Core\Entities\Translation')
                        ->findOneBy(
                            array(
                                /*
                                 * Browser locale is just lang code, we need to convert it to
                                 * a complete locale with region code (fr -> fr_FR)
                                 */
                                'locale'=>Translation::$availableLocalesShortcut[$_locale]
                            )
                        );
        } else {
            $translation = $this->getService('em')
                        ->getRepository('RZ\Renzo\Core\Entities\Translation')
                        ->findDefault();
            $request->setLocale($translation->getShortLocale());
        }
        return $translation;
    }

    /**
     * Default action for any node URL.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param RZ\Renzo\Core\Entities\Node              $node
     * @param RZ\Renzo\Core\Entities\Translation       $translation
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request, Node $node = null, Translation $translation = null)
    {
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
                        ->getRepository('RZ\Renzo\Core\Entities\Translation')
                        ->findOneBy(
                            array(
                                /*
                                 * Browser locale is just lang code, we need to convert it to
                                 * a complete locale with region code (fr -> fr_FR)
                                 */
                                'locale'=>Translation::$availableLocalesShortcut[$_locale]
                            )
                        );
        }

        /*
         * Grab home flagged node
         */
        $node = $this->getService('em')
                ->getRepository('RZ\Renzo\Core\Entities\Node')
                ->findOneBy(
                    array('home'=>true),
                    null,
                    $translation,
                    $this->getSecurityContext()
                );

        $this->storeNodeAndTranslation($node, $translation);

        return new Response(
            $this->getTwig()->render('home.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * @param RZ\Renzo\Core\Entities\Node        $node
     * @param RZ\Renzo\Core\Entities\Translation $translation
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
                    $this->twig,
                    $this->translator,
                    $this->assignation
                );
            }

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
     * @return RZ\Renzo\Core\Entities\Node
     */
    public function getRequestedNode()
    {
        return $this->node;
    }
    /**
     * @return RZ\Renzo\Core\Entities\Translation
     */
    public function getRequestedTranslation()
    {
        return $this->translation;
    }

    /**
     * Append objects to global container.
     *
     * @param Pimple\Container $container
     */
    public static function setupDependencyInjection(Container $container)
    {
        $container->extend('firewallMap', function ($map, $c) {
            /*
             * Prepare app firewall
             */
            $requestMatcher = new RequestMatcher('^/');
            // allows configuration of different access control rules for specific parts of the website.
            //$accessMap = new AccessMap($requestMatcher, array());

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
