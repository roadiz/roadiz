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
use RZ\Roadiz\Core\Entities\Theme;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Exceptions\NoTranslationAvailableException;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Viewers\ViewableInterface;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Base class for Roadiz themes.
 */
class AppController implements ViewableInterface
{
    const AJAX_TOKEN_INTENTION = 'ajax';
    const SCHEMA_TOKEN_INTENTION = 'update_schema';
    const FONT_TOKEN_INTENTION = 'font_request';

    protected $kernel = null;
    /**
     * Inject current Kernel into running controller.
     *
     * @param RZ\Roadiz\Core\Kernel $newKernel
     */
    public function setKernel(Kernel $newKernel)
    {
        $this->kernel = $newKernel;
    }
    /**
     * Get current Roadiz Kernel instance.
     *
     * Prefer this methods instead of calling static getInstance
     * method of RZ\Roadiz\Core\Kernel.
     *
     * @return RZ\Roadiz\Core\Kernel
     */
    public function getKernel()
    {
        return $this->kernel;
    }
    /**
     * Get mixed object from Dependency Injection container.
     *
     * *Alias for `$this->kernel->container[$key]`*
     *
     * Return the container if no key defined.
     *
     * @param string|null $key
     *
     * @return mixed
     */
    public function getService($key = null)
    {
        if (null === $key) {
            return $this->kernel->container;
        } else {
            return $this->kernel->container[$key];
        }
    }
    /**
     * Alias for `$this->kernel->getSecurityContext()`.
     *
     * @return Symfony\Component\Security\Core\SecurityContext
     */
    public function getSecurityContext()
    {
        return $this->kernel->container['securityContext'];
    }
    /**
     * Alias for `$this->kernel->container['em']`.
     *
     * @return Doctrine\ORM\EntityManager
     */
    public function em()
    {
        return $this->kernel->container['em'];
    }

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
     * @return Symfony\Component\Translation\Translator
     */
    public function getTranslator()
    {
        return $this->kernel->container['translator'];
    }

    /**
     * Initialize controller with its twig environment.
     *
     * @param \Symfony\Component\Security\Core\SecurityContext $securityContext
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
        $staticDomain = SettingsBag::get('static_domain_name');

        if (!empty($staticDomain)) {
            return $this->kernel->getStaticBaseUrl() .
            '/themes/' . static::$themeDir . '/static/';
        } else {
            return $this->kernel->getRequest()->getBaseUrl() .
            '/themes/' . static::$themeDir . '/static/';
        }
    }

    /**
     * Force current AppController twig templates compilation.
     *
     * @return boolean
     */
    public static function forceTwigCompilation()
    {
        if (file_exists(static::getViewsFolder())) {
            $ctrl = new static();
            $ctrl->setKernel(Kernel::getInstance());

            try {
                $fs = new Filesystem();
                $fs->remove([Kernel::getService('twig.cacheFolder')]);
            } catch (IOExceptionInterface $e) {
                echo "An error occurred while deleting backend twig cache directory: " . $e->getPath();
            }

            /*
             * Theme templates
             */
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(static::getViewsFolder()),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($iterator as $file) {
                // force compilation
                if ($file->isFile() &&
                    $file->getExtension() == 'twig') {
                    $ctrl->getTwig()->loadTemplate(str_replace(static::getViewsFolder() . '/', '', $file));
                }
            }

            return true;
        } else {
            return false;
        }
    }
    /**
     * {@inheritdoc}
     */
    public function getTwig()
    {
        return $this->getService('twig.environment');
    }

    /**
     * Return a Response from a template string with its rendering assignation.
     *
     * @see http://api.symfony.com/2.6/Symfony/Bundle/FrameworkBundle/Controller/Controller.html#method_render
     *
     * @param  string        $view       Template file path
     * @param  array         $parameters Twig assignation array
     * @param  Response|null $response   Optional Response object to customize response parameters
     * @param  string        $namespace  Twig loader namespace
     *
     * @return Response
     */
    public function render($view, array $parameters = [], Response $response = null, $namespace = "")
    {
        if (null === $response) {
            $response = new Response(
                '',
                Response::HTTP_OK,
                ['content-type' => 'text/html']
            );
        }

        if ($namespace != "") {
            $view = '@' . $namespace . '/' . $view;
        } else {
            // when no namespace is used
            // use current theme directory
            $view = '@' . static::getThemeDir() . '/' . $view;
        }

        $response->setContent($this->kernel->container['twig.environment']->render($view, $parameters));

        return $response;
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
     * - securityContext
     *
     * @return $this
     */
    public function prepareBaseAssignation()
    {
        $this->assignation = [
            'request' => $this->kernel->getRequest(),
            'head' => [
                'ajax' => $this->kernel->getRequest()->isXmlHttpRequest(),
                'cmsVersion' => Kernel::CMS_VERSION,
                'cmsVersionNumber' => Kernel::$cmsVersion,
                'cmsBuild' => Kernel::$cmsBuild,
                'devMode' => (boolean) $this->kernel->container['config']['devMode'],
                'useCdn' => (boolean) SettingsBag::get('use_cdn'),
                'universalAnalyticsId' => SettingsBag::get('universal_analytics_id'),
                'baseUrl' => $this->kernel->getRequest()->getResolvedBaseUrl(),
                'filesUrl' => $this->kernel
                                   ->getRequest()
                                   ->getBaseUrl() . '/' . Document::getFilesFolderName(),
                'resourcesUrl' => $this->getStaticResourcesUrl(),
                'ajaxToken' => $this->getService('csrfProvider')
                                    ->generateCsrfToken(static::AJAX_TOKEN_INTENTION),
                'fontToken' => $this->getService('csrfProvider')
                                    ->generateCsrfToken(static::FONT_TOKEN_INTENTION),
            ],
            'session' => [
                'id' => $this->kernel->getRequest()->getSession()->getId(),
            ],
        ];

        if ($this->getService('securityContext') !== null &&
            $this->getService('securityContext')->getToken() !== null) {
            $this->assignation['securityContext'] = $this->getService('securityContext');
            $this->assignation['session']['user'] = $this->getService('securityContext')
                 ->getToken()
                 ->getUser();
        }

        return $this;
    }

    /**
     * Return a Response with default backend 404 error page.
     *
     * @param string $message Additionnal message to describe 404 error.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function throw404($message = "")
    {
        $this->getService('logger')->error($message);
        $this->assignation['errorMessage'] = $message;

        return new Response(
            $this->getTwig()->render('404.html.twig', $this->assignation),
            Response::HTTP_NOT_FOUND,
            ['content-type' => 'text/html']
        );
    }

    public static function getCalledClass()
    {
        $className = get_called_class();
        if (strpos($className, "\\") !== 0) {
            $className = "\\" . $className;
        }
        return $className;
    }

    /**
     * Return the current Theme
     *
     * @return \RZ\Roadiz\Core\Entities\Theme
     */
    public static function getTheme()
    {
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
        $theme = Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Theme')
            ->findOneBy(['className' => $className]);
        return $theme;
    }

    /**
     * Setup current theme class into database.
     *
     * @return boolean
     */
    public static function setup()
    {
        $theme = static::getTheme();

        $className = static::getCalledClass();

        if ($theme === null) {
            $theme = new Theme();
            $theme->setClassName($className);
            $theme->setBackendTheme(static::isBackendTheme());
            $theme->setAvailable(true);

            Kernel::getService('em')->persist($theme);
            Kernel::getService('em')->flush();

            return true;
        }

        return false;
    }

    /**
     * Enable theme.
     *
     * @return boolean
     */
    public static function enable()
    {
        $theme = static::getTheme();

        if ($theme !== null) {
            $theme->setAvailable(true);
            Kernel::getService('em')->flush();

            return true;
        }

        return false;
    }
    /**
     * Disable theme.
     *
     * @return boolean
     */
    public static function disable()
    {
        $theme = static::getTheme();

        if ($theme !== null) {
            $theme->setAvailable(false);
            Kernel::getService('em')->flush();

            return true;
        }

        return false;
    }

    /**
     * Append objects to the global dependency injection container.
     *
     * @param Pimple\Container $container
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
        $theme = static::getTheme();

        if ($theme !== null) {
            $home = $theme->getHomeNode();
            if ($home !== null) {
                if ($translation !== null) {
                    return $this->getService('em')->getRepository("RZ\Roadiz\Core\Entities\Node")
                                ->findWithTranslation(
                                    $home->getId(),
                                    $translation,
                                    $this->getService("securityContext")
                                );
                } else {
                    return $this->getService('em')->getRepository("RZ\Roadiz\Core\Entities\Node")
                                ->findWithDefaultTranslation(
                                    $home->getId(),
                                    $this->getService("securityContext")
                                );
                }
            }
        }
        if ($translation !== null) {
            return $this->getService('em')->getRepository('RZ\Roadiz\Core\Entities\Node')
                        ->findHomeWithTranslation(
                            $translation,
                            $this->getService("securityContext")
                        );
        } else {
            return $this->getService('em')->getRepository('RZ\Roadiz\Core\Entities\Node')
                        ->findHomeWithDefaultTranslation($this->getService("securityContext"));
        }
    }

    protected function getRoot()
    {
        $theme = static::getTheme();
        return $theme->getRoot();
    }

    /**
     * Publish a message in Session flash bag and
     * logger interface.
     *
     * @param Request $request
     * @param string  $msg
     * @param string  $level
     * @param RZ\Roadiz\Core\Entities\NodesSources $source
     */
    protected function publishMessage(Request $request, $msg, $level = "confirm", NodesSources $source = null)
    {
        $request->getSession()->getFlashBag()->add($level, $msg);

        switch ($level) {
            case 'error':
                $this->getService('logger')->error($msg, ['source' => $source]);
                break;
            default:
                $this->getService('logger')->info($msg, ['source' => $source]);
                break;
        }
    }
    /**
     * Publish a confirm message in Session flash bag and
     * logger interface.
     *
     * @param Request $request
     * @param string  $msg
     * @param RZ\Roadiz\Core\Entities\NodesSources $source
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
     * @param RZ\Roadiz\Core\Entities\NodesSources $source
     */
    public function publishErrorMessage(Request $request, $msg, NodesSources $source = null)
    {
        $this->publishMessage($request, $msg, 'error', $source);
    }

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
     * Custom route for redirecting routes with a trailing slash.
     *
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function removeTrailingSlashAction(Request $request)
    {
        $pathInfo = $request->getPathInfo();
        $requestUri = $request->getRequestUri();

        $url = str_replace($pathInfo, rtrim($pathInfo, ' /'), $requestUri);

        $response = new RedirectResponse($url, 301);
        $response->prepare($request);

        return $response->send();
    }

    /**
     * Validate a request against a given ROLE_* and throws
     * an AccessDeniedException exception.
     *
     * @param string $role
     *
     * @throws AccessDeniedException
     */
    public function validateAccessForRole($role)
    {
        if (!$this->getService('securityContext')->isGranted($role)) {
            throw new AccessDeniedException("You don't have access to this page:" . $role);
        }
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
        $user = $this->getService("securityContext")->getToken()->getUser();
        $node = $this->getService('em')
                     ->find('RZ\Roadiz\Core\Entities\Node', (int) $nodeId);

        if (null !== $node) {
            $this->getService('em')->refresh($node);
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
            !$this->getService('securityContext')->isGranted('ROLE_ACCESS_NEWSLETTERS')) {
            throw new AccessDeniedException("You don't have access to this page");
        } elseif (!$isNewsletterFriend) {
            if (!$this->getService('securityContext')->isGranted($role)) {
                throw new AccessDeniedException("You don't have access to this page");
            }

            if ($user->getChroot() !== null &&
                !in_array($user->getChroot(), $parents, true)) {
                throw new AccessDeniedException("You don't have access to this page");
            }
        }
    }
}
