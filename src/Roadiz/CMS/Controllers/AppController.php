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
 * @file AppController.php
 * @author Ambroise Maupate
 */

namespace RZ\Roadiz\CMS\Controllers;

use Pimple\Container;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Exceptions\ForceResponseException;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Repositories\NodeRepository;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Base class for Roadiz themes.
 */
abstract class AppController extends Controller
{
    const AJAX_TOKEN_INTENTION = 'ajax';
    const SCHEMA_TOKEN_INTENTION = 'update_schema';
    const FONT_TOKEN_INTENTION = 'font_request';

    /**
     * Theme entity.
     *
     * @var \RZ\Roadiz\Core\Entities\Theme;
     */
    protected $theme = null;
    /**
     * Theme name.
     *
     * @var string
     */
    protected static $themeName = '';
    /**
     * @return string
     */
    public static function getThemeName()
    {
        return static::$themeName;
    }

    /**
     * Theme author description.
     *
     * @var string
     */
    protected static $themeAuthor = '';
    /**
     * @return string
     */
    public static function getThemeAuthor()
    {
        return static::$themeAuthor;
    }

    /**
     * Theme copyright licence.
     *
     * @var string
     */
    protected static $themeCopyright = '';
    /**
     * @return string
     */
    public static function getThemeCopyright()
    {
        return static::$themeCopyright;
    }

    /**
     * Theme base directory name.
     *
     * Example: "MyTheme" will be located in "themes/MyTheme"
     * @var string
     */
    protected static $themeDir = '';
    /**
     * @return string
     */
    public static function getThemeDir()
    {
        return static::$themeDir;
    }

    /**
     * @return string Main theme class name
     */
    public static function getThemeMainClassName()
    {
        return static::getThemeDir() . 'App';
    }

    /**
     * @return string Main theme class (FQN class with namespace)
     */
    public static function getThemeMainClass()
    {
        return '\\Themes\\' . static::getThemeDir() . '\\' . static::getThemeMainClassName();
    }

    /**
     * Theme requires a minimal CMS version.
     *
     * Example: "*" will accept any CMS version. Or "3.0.*" will
     * accept any build version of 3.0.
     *
     * @var string
     */
    protected static $themeRequire = '*';
    /**
     * @return string
     */
    public static function getThemeRequire()
    {
        return static::$themeRequire;
    }

    /**
     * Is theme for backend?
     *
     * @var boolean
     */
    protected static $backendTheme = false;
    /**
     * @return boolean
     */
    public static function isBackendTheme()
    {
        return static::$backendTheme;
    }

    /**
     * Assignation for twig template engine.
     *
     * @var array
     */
    protected $assignation = [];

    /**
     * @var Node|null
     */
    private $homeNode = null;

    /**
     * Initialize controller with its twig environment.
     */
    public function __init()
    {
        $this->prepareBaseAssignation();
    }

    /**
     * Return a file locator with theme
     * Resource folder.
     *
     * @return FileLocator
     */
    public static function getFileLocator()
    {
        $resourcesFolder = static::getResourcesFolder();
        return new FileLocator([
            $resourcesFolder,
            $resourcesFolder . '/routing',
            $resourcesFolder . '/config',
        ]);
    }

    /**
     * @return RouteCollection
     */
    public static function getRoutes()
    {
        $locator = static::getFileLocator();
        $loader = new YamlFileLoader($locator);
        return $loader->load('routes.yml');
    }

    /**
     * These routes are used to extend Roadiz back-office.
     *
     * @return RouteCollection
     */
    public static function getBackendRoutes()
    {
        $locator = static::getFileLocator();

        try {
            $loader = new YamlFileLoader($locator);
            return $loader->load('backend-routes.yml');
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * Return theme root folder.
     *
     * @return string
     */
    public static function getThemeFolder()
    {
        $class_info = new \ReflectionClass(static::getThemeMainClass());
        return dirname($class_info->getFileName());
    }

    /**
     * Return theme Resource folder according to
     * main theme class inheriting AppController.
     *
     * Uses \ReflectionClass to resolve final theme class folder
     * whether it’s located in project folder or in vendor folder.
     *
     * @return string
     */
    public static function getResourcesFolder()
    {
        return static::getThemeFolder() . '/Resources';
    }
    /**
     * @return string
     */
    public static function getViewsFolder()
    {
        return static::getResourcesFolder() . '/views';
    }
    /**
     * @return string
     */
    public static function getTranslationsFolder()
    {
        return static::getResourcesFolder() . '/translations';
    }
    /**
     * @return string
     */
    public function getStaticResourcesUrl()
    {
        return $this->get('assetPackages')->getUrl('/themes/' . static::$themeDir . '/static/');
    }

    /**
     * @param string $view
     * @param array $parameters
     * @param Response|null $response
     * @param string $namespace
     * @return Response
     * @throws \Twig_Error_Runtime
     */
    public function render($view, array $parameters = [], Response $response = null, $namespace = "")
    {
        try {
            if (!$this->get('stopwatch')->isStarted('twigRender')) {
                $this->get('stopwatch')->start('twigRender');
            }

            if (null === $response) {
                $response = new Response(
                    '',
                    Response::HTTP_OK,
                    ['content-type' => 'text/html']
                );
            }

            if ($namespace !== "" && $namespace !== "/") {
                $view = '@' . $namespace . '/' . $view;
            } elseif (static::getThemeDir() !== "" && $namespace !== "/") {
                // when no namespace is used
                // use current theme directory
                $view = '@' . static::getThemeDir() . '/' . $view;
            }

            $response->setContent($this->get('twig.environment')->render($view, $parameters));

            return $response;
        } catch (\Twig_Error_Runtime $e) {
            if ($e->getPrevious() instanceof ForceResponseException) {
                return $e->getPrevious()->getResponse();
            } else {
                throw $e;
            }
        }
    }

    /**
     * Prepare base informations to be rendered in twig templates.
     *
     * ## Available contents
     *
     * - request: Main request object
     * - head
     *     - ajax: `boolean`
     *     - cmsVersion
     *     - cmsVersionNumber
     *     - cmsBuild
     *     - devMode: `boolean`
     *     - baseUrl
     *     - filesUrl
     *     - resourcesUrl
     *     - absoluteResourcesUrl
     *     - staticDomainName
     *     - ajaxToken
     *     - fontToken
     *     - universalAnalyticsId
     *     - useCdn
     * - session
     *     - messages
     *     - id
     *     - user
     * - bags
     *     - nodeTypes (ParametersBag)
     *     - settings (ParametersBag)
     *     - roles (ParametersBag)
     * - securityAuthorizationChecker
     *
     * @return $this
     */
    public function prepareBaseAssignation()
    {
        /** @var Kernel $kernel */
        $kernel = $this->get('kernel');
        $this->assignation = [
            'request' => $this->getRequest(),
            'head' => [
                'ajax' => $this->getRequest()->isXmlHttpRequest(),
                'cmsVersion' => Kernel::CMS_VERSION,
                'cmsVersionNumber' => Kernel::$cmsVersion,
                'cmsBuild' => Kernel::$cmsBuild,
                'devMode' => $kernel->isDevMode(),
                'maintenanceMode' => (boolean) $this->get('settingsBag')->get('maintenance_mode'),
                'useCdn' => (boolean) $this->get('settingsBag')->get('use_cdn'),
                'universalAnalyticsId' => $this->get('settingsBag')->get('universal_analytics_id'),
                'baseUrl' => $this->getRequest()->getSchemeAndHttpHost() . $this->getRequest()->getBasePath(),
                'filesUrl' => $this->getRequest()->getBaseUrl() . $kernel->getPublicFilesBasePath(),
                'resourcesUrl' => $this->getStaticResourcesUrl(),
                'absoluteResourcesUrl' => $this->getRequest()->getSchemeAndHttpHost() . $this->getRequest()->getBasePath() . $this->getStaticResourcesUrl(),
                'ajaxToken' => $this->get('csrfTokenManager')->getToken(static::AJAX_TOKEN_INTENTION),
                'fontToken' => $this->get('csrfTokenManager')->getToken(static::FONT_TOKEN_INTENTION),
            ],
            'session' => [
                'id' => $this->getRequest()->getSession()->getId(),
                'user' => $this->getUser(),
            ],
            'bags' => [
                'settings' => $this->get('settingsBag'),
                'roles' => $this->get('rolesBag'),
                'nodeTypes' => $this->get('nodeTypesBag'),
            ]
        ];

        if ('' != $this->get('settingsBag')->getDocument('static_domain_name')) {
            $this->assignation['head']['absoluteResourcesUrl'] = $this->getStaticResourcesUrl();
            $this->assignation['head']['staticDomainName'] = $this->get('settingsBag')->getDocument('static_domain_name');
        }

        if ($this->get('securityAuthorizationChecker') !== null) {
            $this->assignation['authorizationChecker'] = $this->get('securityAuthorizationChecker');
        }

        return $this;
    }

    /**
     * Return a Response with default backend 404 error page.
     *
     * @param string $message Additionnal message to describe 404 error.
     *
     * @return Response
     */
    public function throw404($message = "")
    {
        $this->get('logger')->error($message);
        $this->assignation['errorMessage'] = $message;

        return new Response(
            $this->getTwig()->render('404.html.twig', $this->assignation),
            Response::HTTP_NOT_FOUND,
            ['content-type' => 'text/html']
        );
    }

    /**
     * Return the current Theme
     *
     * @return \RZ\Roadiz\Core\Entities\Theme
     */
    public function getTheme()
    {
        $this->container['stopwatch']->start('getTheme');
        if (null === $this->theme) {
            $className = static::getCalledClass();
            while (!StringHandler::endsWith($className, "App")) {
                $className = get_parent_class($className);
                if ($className === false) {
                    $className = "";
                    break;
                }
                if (strpos($className, "\\") !== 0) {
                    $className = "\\" . $className;
                }
            }
            $this->theme = $this->get('em')
                ->getRepository('RZ\Roadiz\Core\Entities\Theme')
                ->findOneByClassName($className);
        }
        $this->container['stopwatch']->stop('getTheme');
        return $this->theme;
    }

    /**
     * Append objects to the global dependency injection container.
     *
     * @param \Pimple\Container $container
     */
    public static function setupDependencyInjection(Container $container)
    {
        /*
         * Enable theme templates in main namespace and in its own theme namespace.
         */
        $container['twig.loaderFileSystem']->addPath(static::getViewsFolder());
        // Add path into a namespaced loader to enable using same template name
        // over different static themes.
        $container['twig.loaderFileSystem']->addPath(static::getViewsFolder(), static::getThemeDir());
    }

    /**
     * @param Translation|null $translation
     * @return null|Node
     */
    protected function getHome(Translation $translation = null)
    {
        $this->container['stopwatch']->start('getHome');
        if (null === $this->homeNode) {
            $theme = $this->getTheme();
            /** @var NodeRepository $nodeRepository */
            $nodeRepository = $this->get('em')->getRepository('RZ\Roadiz\Core\Entities\Node');

            if ($theme !== null) {
                $home = $theme->getHomeNode();
                if ($home !== null) {
                    if ($translation !== null) {
                        $this->homeNode = $nodeRepository->findWithTranslation(
                            $home->getId(),
                            $translation,
                            $this->get('securityAuthorizationChecker')
                        );
                    } else {
                        $this->homeNode = $nodeRepository->findWithDefaultTranslation(
                            $home->getId(),
                            $this->get('securityAuthorizationChecker')
                        );
                    }
                }
            }
            if ($translation !== null) {
                $this->homeNode = $nodeRepository->findHomeWithTranslation(
                    $translation,
                    $this->get('securityAuthorizationChecker')
                );
            } else {
                $this->homeNode = $nodeRepository->findHomeWithDefaultTranslation($this->get('securityAuthorizationChecker'));
            }
        }
        $this->container['stopwatch']->stop('getHome');

        return $this->homeNode;
    }

    /**
     * @return Node
     */
    protected function getRoot()
    {
        $theme = $this->getTheme();
        return $theme->getRoot();
    }

    /**
     * Publish a message in Session flash bag and
     * logger interface.
     *
     * @param Request $request
     * @param string  $msg
     * @param string  $level
     * @param \RZ\Roadiz\Core\Entities\NodesSources $source
     */
    protected function publishMessage(Request $request, $msg, $level = "confirm", NodesSources $source = null)
    {
        $request->getSession()->getFlashBag()->add($level, $msg);

        switch ($level) {
            case 'error':
                $this->get('logger')->error($msg, ['source' => $source]);
                break;
            default:
                $this->get('logger')->info($msg, ['source' => $source]);
                break;
        }
    }
    /**
     * Publish a confirm message in Session flash bag and
     * logger interface.
     *
     * @param Request $request
     * @param string  $msg
     * @param \RZ\Roadiz\Core\Entities\NodesSources $source
     */
    public function publishConfirmMessage(Request $request, $msg, NodesSources $source = null)
    {
        $this->publishMessage($request, $msg, 'confirm', $source);
    }

    /**
     * Publish an error message in Session flash bag and
     * logger interface.
     *
     * @param Request $request
     * @param string  $msg
     * @param \RZ\Roadiz\Core\Entities\NodesSources $source
     */
    public function publishErrorMessage(Request $request, $msg, NodesSources $source = null)
    {
        $this->publishMessage($request, $msg, 'error', $source);
    }

    /**
     * Validate a request against a given ROLE_*
     * and check chroot and newsletter type/accces
     * and throws an AccessDeniedException exception.
     *
     * @param string $role
     * @param integer|null $nodeId
     * @param boolean|false $includeChroot
     *
     * @throws AccessDeniedException
     */
    public function validateNodeAccessForRole($role, $nodeId = null, $includeChroot = false)
    {
        $user = $this->getUser();
        $node = $this->get('em')
            ->find('RZ\Roadiz\Core\Entities\Node', (int) $nodeId);

        if (null !== $node) {
            $this->get('em')->refresh($node);
            $parents = $node->getHandler()->getParents();

            if ($includeChroot) {
                $parents[] = $node;
            }
            $isNewsletterFriend = $node->getHandler()->isRelatedToNewsletter();
        } else {
            $parents = [];
            $isNewsletterFriend = false;
        }

        if ($isNewsletterFriend &&
            !$this->isGranted('ROLE_ACCESS_NEWSLETTERS')) {
            throw new AccessDeniedException("You don't have access to this page");
        } elseif (!$isNewsletterFriend) {
            if (!$this->isGranted($role)) {
                throw new AccessDeniedException("You don't have access to this page");
            }

            if ($user->getChroot() !== null &&
                !in_array($user->getChroot(), $parents, true)) {
                throw new AccessDeniedException("You don't have access to this page");
            }
        }
    }

    /**
     * Generate a simple view to inform visitors that website is
     * currently unavailable.
     *
     * @param Request $request
     * @return Response
     */
    public function maintenanceAction(Request $request)
    {
        $this->prepareBaseAssignation();

        return new Response(
            $this->renderView('maintenance.html.twig', $this->assignation),
            Response::HTTP_SERVICE_UNAVAILABLE,
            ['content-type' => 'text/html']
        );
    }

    /**
     * Return all Form errors as an array.
     *
     * @param FormInterface $form
     * @return array
     */
    protected function getErrorsAsArray(FormInterface $form)
    {
        $errors = [];
        /** @var FormError $error */
        foreach ($form->getErrors() as $error) {
            if (count($error->getMessageParameters()) > 0) {
                $errors[] = $this->get('translator')->trans($error->getMessageTemplate(), $error->getMessageParameters());
            } else {
                $errors[] = $error->getMessage();
            }
        }

        foreach ($form->all() as $key => $child) {
            $err = $this->getErrorsAsArray($child);
            if ($err) {
                $errors[$key] = $err;
            }
        }
        return $errors;
    }
}
