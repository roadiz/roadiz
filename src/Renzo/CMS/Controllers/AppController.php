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

use RZ\Renzo\Core\Viewers\ViewableInterface;
use \Michelf\Markdown;

use Symfony\Component\Security\Http\Firewall;
use Symfony\Component\Security\Http\FirewallMap;
use Symfony\Component\Security\Http\HttpUtils;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
     * Alias for `$this->getKernel()->getSecurityContext()`.
     *
     * @return Symfony\Component\Security\Core\SecurityContext
     */
    public function getSecurityContext()
    {
        return $this->getKernel()->getSecurityContext();
    }
    /**
     * Alias for `$this->getKernel()->getEntityManager()`.
     *
     * @return Doctrine\ORM\EntityManager
     */
    public function em()
    {
        return $this->getKernel()->getEntityManager();
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
     * @var Psr\Log\LoggerInterface
     */
    protected $logger = null;
    /**
     * @return Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Initialize controller with its twig environment.
     *
     * @param \Symfony\Component\Security\Core\SecurityContext $securityContext
     */
    public function __init(SecurityContext $securityContext = null)
    {
        $this->initializeTwig()
             ->initializeTranslator()
             ->prepareBaseAssignation();

        if (null !== $securityContext) {
            $this->logger = new \RZ\Renzo\Core\Log\Logger();
            $this->logger->setSecurityContext($securityContext);
        }
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
        SecurityContext $securityContext,
        \Twig_Environment $twigEnvironment,
        Translator $translator,
        array $baseAssignation
    ) {
        $this->twig = $twigEnvironment;
        $this->translator = $translator;
        $this->assignation = $baseAssignation;

        if (null !== $securityContext) {
            $this->logger = new \RZ\Renzo\Core\Log\Logger();
            $this->logger->setSecurityContext($securityContext);
        }
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
        //$this->getKernel()->getStopwatch()->start('initTranslations');
        $lang = Kernel::getInstance()->getRequest()->getLocale();
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
        //$this->getKernel()->getStopwatch()->stop('initTranslations');

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function handleTwigCache()
    {
        if (Kernel::getInstance()->isDebug()) {
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
        return Kernel::getInstance()->getRequest()->getBaseUrl().
            '/themes/'.static::$themeDir.'/static/';
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

        $vendorDir = realpath(RENZO_ROOT . '/vendor');
        // le chemin vers TwigBridge pour que Twig puisse localiser
        // le fichier form_div_layout.html.twig
        $vendorTwigBridgeDir =
            $vendorDir . '/symfony/twig-bridge/Symfony/Bridge/Twig';
        // le chemin vers les autres templates


        $loader = new \Twig_Loader_Filesystem(array(
            static::getViewsFolder(), // Theme templates
            RENZO_ROOT . '/src/Renzo/CMS/Resources/views/forms', // Form extension templates
            $vendorTwigBridgeDir . '/Resources/views/Form' // Form extension templates
        ));
        $this->twig = new \Twig_Environment($loader, array(
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
                Kernel::getInstance()->getCsrfProvider()
            ))
        );

        //RoutingExtension
        $this->twig->addExtension(
            new RoutingExtension(Kernel::getInstance()->getUrlGenerator())
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
            $ctrl->initializeTwig();
            $ctrl->initializeTranslator();

            try {
                $fs = new Filesystem();
                $fs->remove(array($ctrl->getCacheDirectory()));
            } catch (IOExceptionInterface $e) {
                echo "An error occurred while deleting backend twig cache directory: ".$e->getPath();
            }
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(static::getViewsFolder()),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($iterator as $file) {
                // force compilation
                if ($file->isFile()) {
                    $ctrl->getTwig()->loadTemplate(str_replace(static::getViewsFolder().'/', '', $file));
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
     * - request: Main http_kernel request object
     * - head
     *     - devMode: boolean
     *     - baseUrl
     *     - filesUrl
     *     - resourcesUrl
     *     - ajaxToken
     *     - fontToken
     * - session
     *     - messages
     *     - id
     *
     * @return $this
     */
    public function prepareBaseAssignation()
    {
        $this->assignation = array(
            'request' => $this->getKernel()->getRequest(),
            'head' => array(
                'ajax' => $this->getKernel()->getRequest()->isXmlHttpRequest(),
                'cmsVersion' => Kernel::CMS_VERSION,
                'cmsBuild' => Kernel::$cmsBuild,
                'devMode' => (boolean) $this->getKernel()->getConfig()['devMode'],
                'baseUrl' => $this->getKernel()->getRequest()->getBaseUrl(),
                'filesUrl' => $this->getKernel()
                                   ->getRequest()
                                   ->getBaseUrl().'/'.Document::getFilesFolderName(),
                'resourcesUrl' => $this->getStaticResourcesUrl(),
                'ajaxToken' => $this->getKernel()
                                    ->getCsrfProvider()
                                    ->generateCsrfToken(static::AJAX_TOKEN_INTENTION),
                'fontToken' => $this->getKernel()
                                    ->getCsrfProvider()
                                    ->generateCsrfToken(static::FONT_TOKEN_INTENTION)
            ),
            'session' => array(
                'messages' => $this->getKernel()->getRequest()->getSession()->getFlashBag()->all(),
                'id' => $this->getKernel()->getRequest()->getSession()->getId()
            )
        );

        if ($this->getKernel()->getSecurityContext() !== null &&
            $this->getKernel()->getSecurityContext()->getToken() !== null ) {

            $this->assignation['session']['user'] = $this->getKernel()
                                                         ->getSecurityContext()
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
        $theme = Kernel::getInstance()->em()
            ->getRepository('RZ\Renzo\Core\Entities\Theme')
            ->findOneBy(array('className'=>$className));

        if ($theme === null) {
            $theme = new Theme();
            $theme->setClassName($className);
            $theme->setBackendTheme(static::isBackendTheme());
            $theme->setAvailable(true);

            Kernel::getInstance()->em()->persist($theme);
            Kernel::getInstance()->em()->flush();

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
        $theme = Kernel::getInstance()->em()
            ->getRepository('RZ\Renzo\Core\Entities\Theme')
            ->findOneBy(array('className'=>$className));

        if ($theme !== null) {
            $theme->setAvailable(true);
            Kernel::getInstance()->em()->flush();

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
        $theme = Kernel::getInstance()->em()
            ->getRepository('RZ\Renzo\Core\Entities\Theme')
            ->findOneBy(array('className'=>$className));

        if ($theme !== null) {
            $theme->setAvailable(false);
            Kernel::getInstance()->em()->flush();

            return true;
        }

        return false;
    }

    /**
     * Register current AppController security scheme in Kernel firewall map.
     *
     * Implements this method if your app controller need a security context.
     *
     * @param SecurityContext           $securityContext
     * @param UserProvider              $renzoUserProvider
     * @param DaoAuthenticationProvider $authenticationManager
     * @param AccessDecisionManager     $accessDecisionManager
     * @param FirewallMap               $firewallMap
     * @param HttpKernelInterface       $httpKernel
     * @param HttpUtils                 $httpUtils
     * @param EventDispatcher           $dispatcher
     *
     * @see BackendController::appendToFirewallMap
     */
    public static function appendToFirewallMap(
        SecurityContext $securityContext,
        UserProvider $renzoUserProvider,
        DaoAuthenticationProvider $authenticationManager,
        AccessDecisionManager $accessDecisionManager,
        FirewallMap $firewallMap,
        HttpKernelInterface $httpKernel,
        HttpUtils $httpUtils,
        EventDispatcher $dispatcher = null
    ) {

    }
}
