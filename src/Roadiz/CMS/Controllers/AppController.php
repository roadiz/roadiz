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
use RZ\Roadiz\Core\Bags\SettingsBag;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Exceptions\ForceResponseException;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Base class for Roadiz themes.
 */
class AppController extends Controller
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
     * Initialize controller with its twig environment.
     */
    public function __init()
    {
        $this->prepareBaseAssignation();
    }

    /**
     * @return RouteCollection
     */
    public static function getRoutes()
    {
        $locator = new FileLocator([
            static::getResourcesFolder(),
        ]);

        if (file_exists(static::getResourcesFolder() . '/routes.yml')) {
            $loader = new YamlFileLoader($locator);

            return $loader->load('routes.yml');
        }

        return null;
    }
    /**
     * These routes are used to extend Roadiz back-office.
     *
     * @return RouteCollection
     */
    public static function getBackendRoutes()
    {
        $locator = new FileLocator([
            static::getResourcesFolder(),
        ]);

        if (file_exists(static::getResourcesFolder() . '/backend-routes.yml')) {
            $loader = new YamlFileLoader($locator);

            return $loader->load('backend-routes.yml');
        }

        return null;
    }

    /**
     * @return string
     */
    public static function getResourcesFolder()
    {
        return ROADIZ_ROOT . '/themes/' . static::$themeDir . '/Resources';
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
    public function getStaticResourcesUrl()
    {
        return $this->getService('assetPackages')->getUrl('/themes/' . static::$themeDir . '/static/');
    }

    /**
     * {@inheritdoc}
     */
    public function render($view, array $parameters = [], Response $response = null, $namespace = "")
    {
        try {
            if (!$this->getService('stopwatch')->isStarted('twigRender')) {
                $this->getService('stopwatch')->start('twigRender');
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

            $response->setContent($this->container['twig.environment']->render($view, $parameters));

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
     *     - ajaxToken
     *     - fontToken
     *     - universalAnalyticsId
     *     - useCdn
     * - session
     *     - messages
     *     - id
     *     - user
     * - securityAuthorizationChecker
     *
     * @return $this
     */
    public function prepareBaseAssignation()
    {
        $this->assignation = [
            'request' => $this->getRequest(),
            'head' => [
                'ajax' => $this->getRequest()->isXmlHttpRequest(),
                'cmsVersion' => Kernel::CMS_VERSION,
                'cmsVersionNumber' => Kernel::$cmsVersion,
                'cmsBuild' => Kernel::$cmsBuild,
                'devMode' => $this->container['kernel']->isDevMode(),
                'useCdn' => (boolean) SettingsBag::get('use_cdn'),
                'universalAnalyticsId' => SettingsBag::get('universal_analytics_id'),
                'baseUrl' => $this->getRequest()->getSchemeAndHttpHost() . $this->getRequest()->getBasePath(),
                'filesUrl' => $this->getRequest()->getBaseUrl() . '/' . Document::getFilesFolderName(),
                'resourcesUrl' => $this->getStaticResourcesUrl(),
                'ajaxToken' => $this->container['csrfTokenManager']->getToken(static::AJAX_TOKEN_INTENTION),
                'fontToken' => $this->container['csrfTokenManager']->getToken(static::FONT_TOKEN_INTENTION),
            ],
            'session' => [
                'id' => $this->getRequest()->getSession()->getId(),
                'user' => $this->getUser(),
            ],
        ];

        if ($this->container['securityAuthorizationChecker'] !== null) {
            $this->assignation['authorizationChecker'] = $this->container['securityAuthorizationChecker'];
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
        $this->container['logger']->error($message);
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
            $this->theme = $this->getService('em')
                ->getRepository('RZ\Roadiz\Core\Entities\Theme')
                ->findOneByClassName($className);
        }
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

    protected function getHome(Translation $translation = null)
    {
        $theme = $this->getTheme();

        if ($theme !== null) {
            $home = $theme->getHomeNode();
            if ($home !== null) {
                if ($translation !== null) {
                    return $this->container['em']->getRepository("RZ\Roadiz\Core\Entities\Node")
                        ->findWithTranslation(
                            $home->getId(),
                            $translation,
                            $this->container['securityAuthorizationChecker']
                        );
                } else {
                    return $this->container['em']->getRepository("RZ\Roadiz\Core\Entities\Node")
                        ->findWithDefaultTranslation(
                            $home->getId(),
                            $this->container['securityAuthorizationChecker']
                        );
                }
            }
        }
        if ($translation !== null) {
            return $this->container['em']->getRepository('RZ\Roadiz\Core\Entities\Node')
                ->findHomeWithTranslation(
                    $translation,
                    $this->container['securityAuthorizationChecker']
                );
        } else {
            return $this->container['em']->getRepository('RZ\Roadiz\Core\Entities\Node')
                ->findHomeWithDefaultTranslation($this->container['securityAuthorizationChecker']);
        }
    }

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
                $this->container['logger']->error($msg, ['source' => $source]);
                break;
            default:
                $this->container['logger']->info($msg, ['source' => $source]);
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
        $node = $this->container['em']
            ->find('RZ\Roadiz\Core\Entities\Node', (int) $nodeId);

        if (null !== $node) {
            $this->container['em']->refresh($node);
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
}
