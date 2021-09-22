<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Controllers;

use Exception;
use InvalidArgumentException;
use Pimple\Container;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionException;
use RZ\Roadiz\Core\AbstractEntities\TranslationInterface;
use RZ\Roadiz\Core\Authorization\Chroot\NodeChrootResolver;
use RZ\Roadiz\Core\Bags\Settings;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Theme;
use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\Core\Events\CachableResponseSubscriber;
use RZ\Roadiz\Core\Handlers\NodeHandler;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Repositories\NodeRepository;
use RZ\Roadiz\Preview\PreviewResolverInterface;
use RZ\Roadiz\Utils\Asset\Packages;
use RZ\Roadiz\Utils\Theme\ThemeResolverInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\String\UnicodeString;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\ConstraintViolation;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;

/**
 * Base class for Roadiz themes.
 */
abstract class AppController extends Controller
{
    const AJAX_TOKEN_INTENTION = 'ajax';
    const SCHEMA_TOKEN_INTENTION = 'update_schema';

    /**
     * @var int Theme priority to load templates and translation in the right order.
     */
    public static int $priority = 0;
    /**
     * Theme name.
     *
     * @var string
     */
    protected static string $themeName = '';
    /**
     * Theme author description.
     *
     * @var string
     */
    protected static string $themeAuthor = '';
    /**
     * Theme copyright licence.
     *
     * @var string
     */
    protected static string $themeCopyright = '';
    /**
     * Theme base directory name.
     *
     * Example: "MyTheme" will be located in "themes/MyTheme"
     * @var string
     */
    protected static string $themeDir = '';
    /**
     * Theme requires a minimal CMS version.
     *
     * Example: "*" will accept any CMS version. Or "3.0.*" will
     * accept any build version of 3.0.
     *
     * @var string
     */
    protected static string $themeRequire = '*';
    /**
     * Is theme for backend?
     *
     * @var bool
     */
    protected static bool $backendTheme = false;
    protected ?Theme $theme = null;
    /**
     * Assignation for twig template engine.
     */
    protected array $assignation = [];
    /**
     * @var Node|null
     */
    private ?Node $homeNode = null;

    /**
     * @return string
     */
    public static function getThemeName(): string
    {
        return static::$themeName;
    }

    /**
     * @return string
     */
    public static function getThemeAuthor(): string
    {
        return static::$themeAuthor;
    }

    /**
     * @return string
     */
    public static function getThemeCopyright(): string
    {
        return static::$themeCopyright;
    }

    /**
     * @return int
     */
    public static function getPriority(): int
    {
        return static::$priority;
    }

    /**
     * @return string
     */
    public static function getThemeRequire(): string
    {
        return static::$themeRequire;
    }

    /**
     * @return boolean
     */
    public static function isBackendTheme(): bool
    {
        return static::$backendTheme;
    }

    /**
     * @return RouteCollection
     * @throws ReflectionException
     */
    public static function getRoutes(): RouteCollection
    {
        $locator = static::getFileLocator();
        $loader = new YamlFileLoader($locator);
        return $loader->load('routes.yml');
    }

    /**
     * Return a file locator with theme
     * Resource folder.
     *
     * @return FileLocator
     * @throws ReflectionException
     */
    public static function getFileLocator(): FileLocator
    {
        $resourcesFolder = static::getResourcesFolder();
        return new FileLocator([
            $resourcesFolder,
            $resourcesFolder . '/routing',
            $resourcesFolder . '/config',
        ]);
    }

    /**
     * Return theme Resource folder according to
     * main theme class inheriting AppController.
     *
     * Uses \ReflectionClass to resolve final theme class folder
     * whether it’s located in project folder or in vendor folder.
     *
     * @return string
     * @throws ReflectionException
     */
    public static function getResourcesFolder(): string
    {
        return static::getThemeFolder() . '/Resources';
    }

    /**
     * Return theme root folder.
     *
     * @return string
     * @throws ReflectionException
     */
    public static function getThemeFolder(): string
    {
        $class_info = new ReflectionClass(static::getThemeMainClass());
        return dirname($class_info->getFileName());
    }

    /**
     * @return class-string Main theme class (FQN class with namespace)
     */
    public static function getThemeMainClass(): string
    {
        return '\\Themes\\' . static::getThemeDir() . '\\' . static::getThemeMainClassName();
    }

    /**
     * @return string
     */
    public static function getThemeDir(): string
    {
        return static::$themeDir;
    }

    /**
     * @return string Main theme class name
     */
    public static function getThemeMainClassName(): string
    {
        return static::getThemeDir() . 'App';
    }

    /**
     * These routes are used to extend Roadiz back-office.
     *
     * @return RouteCollection|null
     * @throws ReflectionException
     */
    public static function getBackendRoutes(): ?RouteCollection
    {
        $locator = static::getFileLocator();

        try {
            $loader = new YamlFileLoader($locator);
            return $loader->load('backend-routes.yml');
        } catch (InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * @return string
     * @throws ReflectionException
     */
    public static function getTranslationsFolder(): string
    {
        return static::getResourcesFolder() . '/translations';
    }

    /**
     * @return string
     * @throws ReflectionException
     */
    public static function getPublicFolder(): string
    {
        return static::getThemeFolder() . '/static';
    }

    /**
     * Append objects to the global dependency injection container.
     *
     * @param Container $container
     *
     * @throws ReflectionException
     * @throws LoaderError
     */
    public static function setupDependencyInjection(Container $container)
    {
        // Do nothing
    }

    /**
     * @param Container $container
     *
     * @throws ReflectionException
     * @throws LoaderError
     */
    public static function addThemeTemplatesPath(Container $container)
    {
        /** @var FilesystemLoader $loader */
        $loader = $container['twig.loaderFileSystem'];
        /*
         * Enable theme templates in main namespace and in its own theme namespace.
         */
        $loader->prependPath(static::getViewsFolder());
        // Add path into a namespaced loader to enable using same template name
        // over different static themes.
        $loader->prependPath(static::getViewsFolder(), static::getThemeDir());
    }

    /**
     * @return string
     * @throws ReflectionException
     */
    public static function getViewsFolder(): string
    {
        return static::getResourcesFolder() . '/views';
    }

    /**
     * @return array
     */
    public function getAssignation(): array
    {
        return $this->assignation;
    }

    /**
     * Initialize controller with its twig environment.
     */
    public function __init()
    {
        $this->prepareBaseAssignation();
    }

    /**
     * Prepare base information to be rendered in twig templates.
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
            'head' => [
                'ajax' => $this->getRequest()->isXmlHttpRequest(),
                'devMode' => $kernel->isDevMode(),
                'maintenanceMode' => (boolean) $this->getSettingsBag()->get('maintenance_mode'),
                'useCdn' => (boolean) $this->getSettingsBag()->get('use_cdn'),
                'universalAnalyticsId' => $this->getSettingsBag()->get('universal_analytics_id'),
                'googleTagManagerId' => $this->getSettingsBag()->get('google_tag_manager_id'),
                'baseUrl' => $this->getRequest()->getSchemeAndHttpHost() . $this->getRequest()->getBasePath(),
                'filesUrl' => $this->getRequest()->getBaseUrl() . $kernel->getPublicFilesBasePath(),
                'resourcesUrl' => $this->getStaticResourcesUrl(),
                'absoluteResourcesUrl' => $this->getAbsoluteStaticResourceUrl(),
            ]
        ];

        if ('' !== $this->get('config')['staticDomainName']) {
            $this->assignation['head']['staticDomainName'] = $this->get('config')['staticDomainName'];
        }

        return $this;
    }

    /**
     * @return string
     * @deprecated Use asset() twig function
     */
    public function getStaticResourcesUrl(): string
    {
        return $this->get('assetPackages')->getUrl('themes/' . static::$themeDir . '/static/');
    }

    /**
     * @return string
     * @deprecated Use absolute_url(asset()) twig function
     */
    public function getAbsoluteStaticResourceUrl(): string
    {
        return $this->get('assetPackages')->getUrl('themes/' . static::$themeDir . '/static/', Packages::ABSOLUTE);
    }

    /**
     * Return a Response with default backend 404 error page.
     *
     * @param string $message Additional message to describe 404 error.
     *
     * @return Response
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function throw404($message = "")
    {
        /** @var LoggerInterface $logger */
        $logger = $this->get('logger');
        $logger->warning($message);

        $this->assignation['nodeName'] = 'error-404';
        $this->assignation['nodeTypeName'] = 'error404';
        $this->assignation['errorMessage'] = $message;
        $this->assignation['title'] = $this->get('translator')->trans('error404.title');
        $this->assignation['content'] = $this->get('translator')->trans('error404.message');

        return new Response(
            $this->getTwig()->render('404.html.twig', $this->assignation),
            Response::HTTP_NOT_FOUND,
            ['content-type' => 'text/html']
        );
    }

    /**
     * Return the current Theme
     *
     * @return Theme|null
     */
    public function getTheme(): ?Theme
    {
        $this->container['stopwatch']->start('getTheme');
        /** @var ThemeResolverInterface $themeResolver */
        $themeResolver = $this->get('themeResolver');
        if (null === $this->theme) {
            $className = new UnicodeString(static::getCalledClass());
            while (!$className->endsWith('App')) {
                $className = get_parent_class($className->toString());
                if ($className === false) {
                    $className = new UnicodeString('');
                    break;
                }
                $className = new UnicodeString($className);
            }
            $this->theme = $themeResolver->findThemeByClass($className->toString());
        }
        $this->container['stopwatch']->stop('getTheme');
        return $this->theme;
    }

    /**
     * Publish a confirm message in Session flash bag and
     * logger interface.
     *
     * @param Request $request
     * @param string $msg
     * @param NodesSources|null $source
     */
    public function publishConfirmMessage(Request $request, string $msg, ?NodesSources $source = null): void
    {
        $this->publishMessage($request, $msg, 'confirm', $source);
    }

    /**
     * Publish a message in Session flash bag and
     * logger interface.
     *
     * @param Request $request
     * @param string $msg
     * @param string $level
     * @param NodesSources|null $source
     */
    protected function publishMessage(
        Request $request,
        string $msg,
        string $level = "confirm",
        ?NodesSources $source = null
    ): void {
        $session = $this->getSession();
        if (null !== $session && $session instanceof Session) {
            $session->getFlashBag()->add($level, $msg);
        }

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
     * Returns the current session.
     *
     * @return SessionInterface|null
     */
    public function getSession(): ?SessionInterface
    {
        $request = $this->getRequest();
        return null !== $request && $request->hasPreviousSession() ? $request->getSession() : null;
    }

    /**
     * Publish an error message in Session flash bag and
     * logger interface.
     *
     * @param Request $request
     * @param string $msg
     * @param NodesSources|null $source
     */
    public function publishErrorMessage(Request $request, string $msg, NodesSources $source = null)
    {
        $this->publishMessage($request, $msg, 'error', $source);
    }

    /**
     * Validate a request against a given ROLE_*
     * and check chroot
     * and throws an AccessDeniedException exception.
     *
     * @param mixed $attributes
     * @param int|null $nodeId
     * @param bool|false $includeChroot
     *
     * @throws AccessDeniedException
     */
    public function validateNodeAccessForRole($attributes, ?int $nodeId = null, bool $includeChroot = false)
    {
        /** @var User $user */
        $user = $this->getUser();
        /** @var NodeChrootResolver $chrootResolver */
        $chrootResolver = $this->get(NodeChrootResolver::class);
        $chroot = $chrootResolver->getChroot($user);

        if ($this->isGranted($attributes) && $chroot === null) {
            /*
             * Already grant access if user is not chrooted.
             */
            return;
        }

        /** @var Node|null $node */
        $node = $this->get('em')->find(Node::class, (int) $nodeId);

        if (null !== $node) {
            $this->get('em')->refresh($node);

            /** @var NodeHandler $nodeHandler */
            $nodeHandler = $this->get('factory.handler')->getHandler($node);
            $parents = $nodeHandler->getParents();

            if ($includeChroot) {
                $parents[] = $node;
            }
        } else {
            $parents = [];
        }

        if (!$this->isGranted($attributes)) {
            throw new AccessDeniedException("You don't have access to this page");
        }

        if (null !== $user && $chroot !== null && !in_array($chroot, $parents, true)) {
            throw new AccessDeniedException("You don't have access to this page");
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
     * Make current response cachable by reverse proxy and browsers.
     *
     * Pay attention that, some reverse proxies systems will need to remove your response
     * cookies header to actually save your response.
     *
     * Do not cache, if
     * - we are in preview mode
     * - we are in debug mode
     * - Request forbids cache
     * - we are in maintenance mode
     * - this is a sub-request
     *
     * @param Request $request
     * @param Response $response
     * @param int $minutes TTL in minutes
     * @param bool $allowClientCache Allows browser level cache
     *
     * @return Response
     */
    public function makeResponseCachable(
        Request $request,
        Response $response,
        int $minutes,
        bool $allowClientCache = false
    ) {
        /** @var Kernel $kernel */
        $kernel = $this->get('kernel');
        /** @var RequestStack $requestStack */
        $requestStack = $kernel->get('requestStack');
        $settings = $this->getSettingsBag();
        /** @var PreviewResolverInterface $previewResolver */
        $previewResolver = $this->get(PreviewResolverInterface::class);

        if (!$previewResolver->isPreview() &&
            !$kernel->isDebug() &&
            $requestStack->getMasterRequest() === $request &&
            $request->isMethodCacheable() &&
            $minutes > 0 &&
            !$settings->get('maintenance_mode', false)) {
            /*
             * TODO: Need refactoring
             * This method is not futureproof and assume that each request
             * is served during one Roadiz lifecycle.
             */
            /** @var EventDispatcherInterface $dispatcher */
            $dispatcher = $this->get('dispatcher');
            $dispatcher->addSubscriber(new CachableResponseSubscriber(
                $minutes,
                true,
                $allowClientCache
            ));
        }

        return $response;
    }

    /**
     * Returns a fully qualified view path for Twig rendering.
     *
     * @param string $view
     * @param string $namespace
     * @return string
     */
    protected function getNamespacedView(string $view, string $namespace = ''): string
    {
        if ($namespace !== "" && $namespace !== "/") {
            $view = '@' . $namespace . '/' . $view;
        } elseif (static::getThemeDir() !== "" && $namespace !== "/") {
            // when no namespace is used
            // use current theme directory
            $view = '@' . static::getThemeDir() . '/' . $view;
        }

        return $view;
    }

    /**
     * @param TranslationInterface|null $translation
     * @return null|Node
     */
    protected function getHome(?TranslationInterface $translation = null): ?Node
    {
        $this->container['stopwatch']->start('getHome');
        if (null === $this->homeNode) {
            /** @var NodeRepository $nodeRepository */
            $nodeRepository = $this->get('em')->getRepository(Node::class);

            if ($translation !== null) {
                $this->homeNode = $nodeRepository->findHomeWithTranslation($translation);
            } else {
                $this->homeNode = $nodeRepository->findHomeWithDefaultTranslation();
            }
        }
        $this->container['stopwatch']->stop('getHome');

        return $this->homeNode;
    }

    /**
     * Return all Form errors as an array.
     *
     * @param FormInterface $form
     * @return array
     */
    protected function getErrorsAsArray(FormInterface $form): array
    {
        /** @var Translator $translator */
        $translator = $this->get('translator');
        $errors = [];
        /** @var FormError $error */
        foreach ($form->getErrors() as $error) {
            $errorFieldName = $error->getOrigin()->getName();
            if (count($error->getMessageParameters()) > 0) {
                if (null !== $error->getMessagePluralization()) {
                    $errors[$errorFieldName] = $translator->transChoice($error->getMessageTemplate(), $error->getMessagePluralization(), $error->getMessageParameters());
                } else {
                    $errors[$errorFieldName] = $translator->trans($error->getMessageTemplate(), $error->getMessageParameters());
                }
            } else {
                $errors[$errorFieldName] = $error->getMessage();
            }
            $cause = $error->getCause();
            if (null !== $cause) {
                if ($cause instanceof ConstraintViolation) {
                    $cause = $cause->getCause();
                }
                if (null !== $cause && is_object($cause)) {
                    if ($cause instanceof Exception) {
                        $errors[$errorFieldName . '_cause_message'] = $cause->getMessage();
                    }
                    $errors[$errorFieldName . '_cause'] = get_class($cause);
                }
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
