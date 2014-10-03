<?php
/**
 * Copyright REZO ZERO 2014
 *
 * @file BackendController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\CMS\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Theme;
use RZ\Renzo\Core\Entities\Document;
use RZ\Renzo\Core\Handlers\UserProvider;
use RZ\Renzo\Core\Handlers\UserHandler;

use Pimple\Container;

use RZ\Renzo\Core\Viewers\ViewableInterface;
use \Michelf\Markdown;

use Symfony\Component\Security\Http\Firewall;
use Symfony\Component\Security\Http\FirewallMap;
use Symfony\Component\Security\Http\HttpUtils;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\EventDispatcher\EventDispatcher;

use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\Csrf\CsrfProvider\SessionCsrfProvider;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\Validation;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\RoutingExtension;
use Symfony\Bridge\Twig\Form\TwigRenderer;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Bridge\Twig\Extension\TranslationExtension;

use Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserChecker;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\Authorization\Voter\RoleVoter;

/**
 * Base class for Renzo themes.
 */
class AppController implements ViewableInterface
{
    const AJAX_TOKEN_INTENTION = 'ajax';
    const SCHEMA_TOKEN_INTENTION = 'update_schema';
    const FONT_TOKEN_INTENTION = 'font_request';


    private $kernel = null;
    /**
     * Inject current Kernel into running controller.
     *
     * @param RZ\Renzo\Core\Kernel $newKernel
     */
    public function setKernel(Kernel $newKernel)
    {
        $this->kernel = $newKernel;
    }
    /**
     * Get current RZCMS Kernel instance.
     *
     * Prefer this methods instead of calling static getInstance
     * method of RZ\Renzo\Core\Kernel.
     *
     * @return RZ\Renzo\Core\Kernel
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
     * @param string $key
     *
     * @return mixed
     */
    public function getService($key)
    {
        return $this->kernel->container[$key];
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
     * Alias for `$this->kernel->getEntityManager()`.
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
     * Twig environment instance.
     *
     * @var \Twig_Environment
     */
    protected $twig = null;
    /**
     * Assignation for twig template engine.
     *
     * @var array
     */
    protected $assignation = array();

    /**
     * @var Symfony\Component\Translation\Translator
     */
    protected $translator = null;
    /**
     * @return Symfony\Component\Translation\Translator
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * Initialize controller with its twig environment.
     *
     * @param \Symfony\Component\Security\Core\SecurityContext $securityContext
     */
    public function __init()
    {
        $this->initializeTwig()
             ->initializeTranslator()
             ->prepareBaseAssignation();
    }

    /**
     * Initialize controller with environment from an other controller
     * in order to avoid initializing same componant again.
     *
     * @param \Symfony\Component\Security\Core\SecurityContext $securityContext
     * @param \Twig_Environment                                $twigEnvironment
     * @param Translator                                       $translator
     * @param array                                            $baseAssignation
     */
    public function __initFromOtherController(
        \Twig_Environment $twigEnvironment,
        Translator $translator,
        array $baseAssignation
    ) {
        $this->twig = $twigEnvironment;
        $this->translator = $translator;
        $this->assignation = $baseAssignation;
    }

    /**
     * @return RouteCollection
     */
    public static function getRoutes()
    {
        $locator = new FileLocator(array(
            static::getResourcesFolder()
        ));

        if (file_exists(static::getResourcesFolder().'/routes.yml')) {
            $loader = new YamlFileLoader($locator);

            return $loader->load('routes.yml');
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function initializeTranslator()
    {
        $this->getService('stopwatch')->start('initTranslations');
        $lang = $this->kernel->getRequest()->getLocale();
        $msgPath = static::getResourcesFolder().'/translations/messages.'.$lang.'.xlf';

        /*
         * fallback to english, if message catalog absent
         */
        if (!file_exists($msgPath)) {
            $lang = 'en';
        }

        // instancier un objet de la classe Translator
        $this->translator = new Translator($lang);
        // charger, en quelque sorte, des traductions dans ce translator
        $this->translator->addLoader('xlf', new XliffFileLoader());
        $this->translator->addResource(
            'xlf',
            static::getResourcesFolder().'/translations/messages.'.$lang.'.xlf',
            $lang
        );
        // ajoutez le TranslationExtension (nous donnant les filtres trans et transChoice)
        $this->twig->addExtension(new TranslationExtension($this->translator));
        $this->twig->addExtension(new \Twig_Extensions_Extension_Intl());
        $this->getService('stopwatch')->stop('initTranslations');

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function handleTwigCache()
    {
        if ($this->kernel->isDebug()) {
            try {
                $fs = new Filesystem();
                $fs->remove(array($this->getCacheDirectory()));
            } catch (IOExceptionInterface $e) {
                echo "An error occurred while deleting backend twig cache directory: ".$e->getPath();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDirectory()
    {
        return RENZO_ROOT.'/cache/'.static::$themeDir.'/twig_cache';
    }
    /**
     * @return string
     */
    public static function getResourcesFolder()
    {
        return RENZO_ROOT.'/themes/'.static::$themeDir.'/Resources';
    }
    /**
     * @return string
     */
    public static function getViewsFolder()
    {
        return static::getResourcesFolder().'/views';
    }
    /**
     * @return string
     */
    public function getStaticResourcesUrl()
    {
        return $this->kernel->getRequest()->getBaseUrl().
            '/themes/'.static::$themeDir.'/static/';
    }

    /**
     * Return every paths to search for twig templates.
     *
     * Extend this method in your custom theme if you need to
     * search additionnal templates.
     *
     * @return \Twig_Loader_Filesystem
     */
    public function getTwigLoader()
    {
        $vendorDir = realpath(RENZO_ROOT . '/vendor');

        // le chemin vers TwigBridge pour que Twig puisse localiser
        // le fichier form_div_layout.html.twig
        $vendorTwigBridgeDir =
            $vendorDir . '/symfony/twig-bridge/Symfony/Bridge/Twig';

        // le chemin vers les autres templates
        return new \Twig_Loader_Filesystem(array(
            static::getViewsFolder(), // Theme templates
            RENZO_ROOT . '/src/Renzo/CMS/Resources/views/forms', // Form extension templates
            $vendorTwigBridgeDir . '/Resources/views/Form' // Form extension templates
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function initializeTwig()
    {
        $this->handleTwigCache();
        /*
         * Enabling forms
         */
        // le fichier Twig contenant toutes les balises pour afficher les formulaires
        // ce fichier vient avoir le TwigBridge
        $defaultFormTheme = 'form_div_layout.html.twig';

        $this->twig = new \Twig_Environment($this->getTwigLoader(), array(
            'cache' => $this->getCacheDirectory(),
        ));

        $formEngine = new TwigRendererEngine(array(
            $defaultFormTheme,
            'fields.html.twig'
        ));

        $formEngine->setEnvironment($this->twig);
        // ajoutez à Twig la FormExtension
        $this->twig->addExtension(
            new FormExtension(new TwigRenderer(
                $formEngine,
                $this->getService('csrfProvider')
            ))
        );

        //RoutingExtension
        $this->twig->addExtension(
            new RoutingExtension($this->getService('urlGenerator'))
        );

        /*
         * ============================================================================
         * Dump
         * ============================================================================
         */
        $dump = new \Twig_SimpleFilter('dump', function ($object) {
            return var_dump($object);
        });
        $this->twig->addFilter($dump);

        /*
         * ============================================================================
         * Markdown
         * ============================================================================
         */
        $markdown = new \Twig_SimpleFilter('markdown', function ($object) {
            return Markdown::defaultTransform($object);
        }, array('is_safe' => array('html')));
        $this->twig->addFilter($markdown);

        return $this;
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
            $ctrl->initializeTwig();
            $ctrl->initializeTranslator();

            try {
                $fs = new Filesystem();
                $fs->remove(array($ctrl->getCacheDirectory()));
            } catch (IOExceptionInterface $e) {
                echo "An error occurred while deleting backend twig cache directory: ".$e->getPath();
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
                    $ctrl->getTwig()->loadTemplate(str_replace(static::getViewsFolder().'/', '', $file));
                }
            }
            /*
             * Common templates
             */
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(RENZO_ROOT.'/src/Renzo/CMS/Resources/views/forms'),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($iterator as $file) {
                // force compilation
                if ($file->isFile() &&
                    $file->getExtension() == 'twig') {
                    $ctrl->getTwig()->loadTemplate(str_replace(RENZO_ROOT.'/src/Renzo/CMS/Resources/views/forms/', '', $file));
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
        return $this->twig;
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
     *     - cmsBuild
     *     - devMode: `boolean`
     *     - baseUrl
     *     - filesUrl
     *     - resourcesUrl
     *     - ajaxToken
     *     - fontToken
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
        $this->assignation = array(
            'request' => $this->kernel->getRequest(),
            'head' => array(
                'ajax' => $this->kernel->getRequest()->isXmlHttpRequest(),
                'cmsVersion' => Kernel::CMS_VERSION,
                'cmsBuild' => Kernel::$cmsBuild,
                'devMode' => (boolean) $this->kernel->container['config']['devMode'],
                'baseUrl' => $this->kernel->getRequest()->getBaseUrl(),
                'filesUrl' => $this->kernel
                                   ->getRequest()
                                   ->getBaseUrl().'/'.Document::getFilesFolderName(),
                'resourcesUrl' => $this->getStaticResourcesUrl(),
                'ajaxToken' => $this->getService('csrfProvider')
                                    ->generateCsrfToken(static::AJAX_TOKEN_INTENTION),
                'fontToken' => $this->getService('csrfProvider')
                                    ->generateCsrfToken(static::FONT_TOKEN_INTENTION)
            ),
            'session' => array(
                'messages' => $this->kernel->getRequest()->getSession()->getFlashBag()->all(),
                'id' => $this->kernel->getRequest()->getSession()->getId()
            )
        );

        if ($this->getService('securityContext') !== null &&
            $this->getService('securityContext')->getToken() !== null ) {

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
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function throw404($message = "")
    {
        $this->assignation['errorMessage'] = $message;

        return new Response(
            $this->getTwig()->render('404.html.twig', $this->assignation),
            Response::HTTP_NOT_FOUND,
            array('content-type' => 'text/html')
        );
    }

    /**
     * Setup current theme class into database.
     *
     * @return boolean
     */
    public static function setup()
    {
        $className = get_called_class();
        $theme = Kernel::getService('em')
            ->getRepository('RZ\Renzo\Core\Entities\Theme')
            ->findOneBy(array('className'=>$className));

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
        $className = get_called_class();
        $theme = Kernel::getService('em')
            ->getRepository('RZ\Renzo\Core\Entities\Theme')
            ->findOneBy(array('className'=>$className));

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
        $className = get_called_class();
        $theme = Kernel::getService('em')
            ->getRepository('RZ\Renzo\Core\Entities\Theme')
            ->findOneBy(array('className'=>$className));

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
}
