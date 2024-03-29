<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Controllers;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\AbstractEntities\TranslationInterface;
use RZ\Roadiz\Core\Bags\Settings;
use RZ\Roadiz\Core\ContainerAwareInterface;
use RZ\Roadiz\Core\ContainerAwareTrait;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Exceptions\ForceResponseException;
use RZ\Roadiz\Core\Exceptions\NoTranslationAvailableException;
use RZ\Roadiz\Core\ListManagers\EntityListManager;
use RZ\Roadiz\Core\ListManagers\EntityListManagerInterface;
use RZ\Roadiz\Core\Repositories\TranslationRepository;
use RZ\Roadiz\Preview\PreviewResolverInterface;
use RZ\Roadiz\Utils\ContactFormManager;
use RZ\Roadiz\Utils\EmailManager;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Translation\Translator;
use Twig\Environment;
use Twig\Error\RuntimeError;

/**
 * Base controller.
 */
abstract class Controller implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * Get current request.
     *
     * @return Request|null
     */
    public function getRequest()
    {
        /** @var RequestStack $requestStack */
        $requestStack = $this->get('requestStack');
        return $requestStack->getCurrentRequest();
    }

    /**
     * Get mixed object from Dependency Injection container.
     *
     * *Alias for `$this->container[$key]`*
     *
     * @param string|null $key
     * @return mixed
     * @deprecated Use Controller::get to better match Symfony style.
     */
    public function getService($key = null)
    {
        return $this->container[$key];
    }

    /**
     * Alias for `$this->container['securityAuthorizationChecker']`.
     *
     * @return AuthorizationChecker
     */
    public function getAuthorizationChecker()
    {
        return $this->get('securityAuthorizationChecker');
    }

    /**
     * Alias for `$this->container['securityTokenStorage']`.
     *
     * @return TokenStorageInterface
     */
    public function getTokenStorage()
    {
        return $this->get('securityTokenStorage');
    }

    /**
     * Alias for `$this->container['em']`.
     *
     * @return EntityManager
     */
    public function em()
    {
        return $this->get('em');
    }

    /**
     * @return Translator
     */
    public function getTranslator()
    {
        return $this->get('translator');
    }

    /**
     * @return Settings
     */
    public function getSettingsBag(): Settings
    {
        return $this->get('settingsBag');
    }

    /**
     * @return Environment
     */
    public function getTwig()
    {
        return $this->get('twig.environment');
    }

    /**
     * Wrap `$this->container['urlGenerator']->generate`
     *
     * @param string|NodesSources $route
     * @param array $parameters
     * @param int $referenceType
     *
     * @return string
     */
    public function generateUrl($route, array $parameters = [], int $referenceType = Router::ABSOLUTE_PATH): string
    {
        if ($route instanceof NodesSources) {
            return $this->get('router')->generate(
                RouteObjectInterface::OBJECT_BASED_ROUTE_NAME,
                array_merge($parameters, [RouteObjectInterface::ROUTE_OBJECT => $route]),
                $referenceType
            );
        }
        return $this->get('router')->generate($route, $parameters, $referenceType);
    }

    /**
     * Returns a RedirectResponse to the given URL.
     */
    protected function redirect(string $url, int $status = 302): RedirectResponse
    {
        return new RedirectResponse($url, $status);
    }

    /**
     * Returns a RedirectResponse to the given route with the given parameters.
     */
    protected function redirectToRoute($route, array $parameters = [], int $status = 302): RedirectResponse
    {
        return $this->redirect($this->generateUrl($route, $parameters), $status);
    }

    /**
     * @param object $event
     * @return object The passed $event MUST be returned
     */
    protected function dispatchEvent($event)
    {
        return $this->get('dispatcher')->dispatch($event);
    }

    /**
     * @return string
     */
    public static function getCalledClass()
    {
        $className = get_called_class();
        if (strpos($className, "\\") !== 0) {
            $className = "\\" . $className;
        }
        return $className;
    }

    /**
     * Validate a request against a given ROLE_* and throws
     * an AccessDeniedException exception.
     *
     * @param string $role
     * @deprecated Use denyAccessUnlessGranted() method instead
     * @throws AccessDeniedException
     */
    public function validateAccessForRole($role)
    {
        if (!$this->isGranted($role)) {
            throw new AccessDeniedException("You don't have access to this page:" . $role);
        }
    }

    /**
     * Custom route for redirecting routes with a trailing slash.
     *
     * @param  Request $request
     *
     * @return RedirectResponse
     */
    public function removeTrailingSlashAction(Request $request)
    {
        $pathInfo = $request->getPathInfo();
        $requestUri = $request->getRequestUri();

        $url = str_replace($pathInfo, rtrim($pathInfo, ' /'), $requestUri);
        // Fix CVE where user could be redirected to external website URL
        $url = preg_replace('#[/]{2,}#', '/', $url);

        return $this->redirect($url, Response::HTTP_MOVED_PERMANENTLY);
    }

    /**
     * Make translation variable with the good localization.
     *
     * @param Request $request
     * @param string $_locale
     *
     * @return TranslationInterface
     * @throws NoTranslationAvailableException
     */
    protected function bindLocaleFromRoute(Request $request, $_locale = null): TranslationInterface
    {
        /*
         * If you use a static route for Home page
         * we need to grab manually language.
         *
         * Get language from static route
         */
        $translation = $this->findTranslationForLocale($_locale);
        $request->setLocale($translation->getPreferredLocale());
        return $translation;
    }

    /**
     * @param string|null $_locale
     *
     * @return TranslationInterface
     */
    protected function findTranslationForLocale(string $_locale = null): TranslationInterface
    {
        if (null === $_locale) {
            return $this->get('defaultTranslation');
        }
        /** @var TranslationRepository $repository */
        $repository = $this->get('em')->getRepository(Translation::class);

        if ($this->get(PreviewResolverInterface::class)->isPreview()) {
            $translation = $repository->findOneByLocaleOrOverrideLocale($_locale);
        } else {
            $translation = $repository->findOneAvailableByLocaleOrOverrideLocale($_locale);
        }

        if (null !== $translation) {
            return $translation;
        }

        throw new NoTranslationAvailableException();
    }

    /**
     * Returns a rendered view.
     *
     * @param  string $view
     * @param  array $parameters
     *
     * @return string
     */
    public function renderView($view, array $parameters = [])
    {
        return $this->get('twig.environment')->render($view, $parameters);
    }

    /**
     * Return a Response from a template string with its rendering assignation.
     *
     * @see http://api.symfony.com/2.6/Symfony/Bundle/FrameworkBundle/Controller/Controller.html#method_render
     *
     * @param string        $view Template file path
     * @param array         $parameters Twig assignation array
     * @param Response|null $response Optional Response object to customize response parameters
     * @param string        $namespace Twig loader namespace
     *
     * @return Response
     * @throws RuntimeError
     */
    public function render($view, array $parameters = [], Response $response = null, $namespace = "")
    {
        if (!$this->get('stopwatch')->isStarted('twigRender')) {
            $this->get('stopwatch')->start('twigRender');
        }

        try {
            if (null === $response) {
                $response = new Response(
                    '',
                    Response::HTTP_OK,
                    ['content-type' => 'text/html']
                );
            }
            $response->setContent($this->renderView($this->getNamespacedView($view, $namespace), $parameters));

            return $response;
        } catch (RuntimeError $e) {
            if ($e->getPrevious() instanceof ForceResponseException) {
                return $e->getPrevious()->getResponse();
            } else {
                throw $e;
            }
        }
    }

    /**
     * @param string $view
     * @param string $namespace
     * @return string
     */
    protected function getNamespacedView(string $view, string $namespace = ''): string
    {
        if ($namespace !== "" && $namespace !== "/") {
            return '@' . $namespace . '/' . $view;
        }

        return $view;
    }

    /**
     * @param array $data
     * @param int $httpStatus
     * @return JsonResponse
     */
    public function renderJson(array $data = [], $httpStatus = JsonResponse::HTTP_OK)
    {
        return new JsonResponse($data, $httpStatus);
    }

    /**
     * Forwards the request to another controller.
     *
     * @param string $controller The controller name (a string like BlogBundle:Post:index)
     * @param array  $path       An array of path parameters
     * @param array  $query      An array of query parameters
     *
     * @return Response A Response instance
     */
    protected function forward($controller, array $path = [], array $query = [])
    {
        $path['_controller'] = $controller;
        /** @var RequestStack $requestStack */
        $requestStack = $this->get('requestStack');
        $subRequest = $requestStack->getCurrentRequest()->duplicate($query, null, $path);
        return $this->get('httpKernel')->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }

    /**
     * Returns a ResourceNotFoundException.
     *
     * This will result in a 404 response code. Usage example:
     *
     *     throw $this->createNotFoundException('Page not found!');
     *
     * @param string          $message  A message
     * @param \Exception|null $previous The previous exception
     *
     * @return ResourceNotFoundException
     */
    protected function createNotFoundException($message = 'Not Found', \Exception $previous = null)
    {
        return new ResourceNotFoundException($message, 0, $previous);
    }

    /**
     * Throw a NotFoundException if request format is not accepted.
     *
     * @param Request $request
     * @param array $acceptableFormats
     */
    protected function denyResourceExceptForFormats(Request $request, $acceptableFormats = ['html'])
    {
        if (!in_array($request->get('_format', 'html'), $acceptableFormats)) {
            throw $this->createNotFoundException(sprintf(
                'Resource not found for %s format',
                $request->get('_format', 'html')
            ));
        }
    }

    /**
     * Returns an AccessDeniedException.
     *
     * This will result in a 403 response code. Usage example:
     *
     *     throw $this->createAccessDeniedException('Unable to access this page!');
     *
     * @param string          $message  A message
     * @param \Exception|null $previous The previous exception
     *
     * @return AccessDeniedException
     */
    protected function createAccessDeniedException($message = 'Access Denied', \Exception $previous = null)
    {
        return new AccessDeniedException($message, $previous);
    }
    /**
     * Creates and returns a Form instance from the type of the form.
     *
     * @param class-string $type The built type of the form
     * @param mixed|null $data    The initial data for the form
     * @param array $options Options for the form
     * @return FormInterface
     */
    protected function createForm(string $type, $data = null, array $options = []): FormInterface
    {
        return $this->get('formFactory')->create($type, $data, $options);
    }

    /**
     * Creates and returns a form builder instance.
     *
     * @param mixed $data    The initial data for the form
     * @param array $options Options for the form
     *
     * @return FormBuilderInterface
     */
    protected function createFormBuilder($data = null, array $options = [])
    {
        return $this->get('formFactory')->createBuilder(FormType::class, $data, $options);
    }

    /**
     * Creates and returns a form builder instance.
     *
     * @param string $name Form name
     * @param mixed $data The initial data for the form
     * @param array $options Options for the form
     *
     * @return FormBuilderInterface
     */
    protected function createNamedFormBuilder($name = 'form', $data = null, array $options = [])
    {
        /** @var FormFactory $formFactory */
        $formFactory = $this->get('formFactory');
        return $formFactory->createNamedBuilder($name, FormType::class, $data, $options);
    }


    /**
     * Creates and returns an EntityListManager instance.
     *
     * @param mixed $entity Entity class path
     * @param array $criteria
     * @param array $ordering
     *
     * @return EntityListManagerInterface
     */
    public function createEntityListManager($entity, array $criteria = [], array $ordering = [])
    {
        /** @var RequestStack $requestStack */
        $requestStack = $this->get('requestStack');
        return new EntityListManager(
            $requestStack->getCurrentRequest(),
            $this->get('em'),
            $entity,
            $criteria,
            $ordering
        );
    }

    /**
     * Create and return a ContactFormManager to build and send contact
     * form by email.
     *
     * @return ContactFormManager
     */
    public function createContactFormManager()
    {
        return $this->get('contactFormManager');
    }

    /**
     * Create and return a EmailManager to build and send emails.
     *
     * @return EmailManager
     */
    public function createEmailManager()
    {
        return $this->get('emailManager');
    }

    /**
     * Get a user from the tokenStorage.
     *
     * @return UserInterface|object|null
     *
     * @throws \LogicException If tokenStorage is not available
     *
     * @see TokenInterface::getUser()
     */
    protected function getUser()
    {
        if (!$this->has('securityTokenStorage')) {
            throw new \LogicException('No TokenStorage has been registered in your application.');
        }

        /** @var TokenInterface|null $token */
        $token = $this->container['securityTokenStorage']->getToken();
        if (null === $token) {
            return null;
        }

        $user = $token->getUser();

        return \is_object($user) ? $user : null;
    }

    /**
     * Checks if the attributes are granted against the current authentication token and optionally supplied object.
     *
     * @param mixed $attributes The attributes
     * @param mixed $object     The object
     *
     * @throws \LogicException
     * @return bool
     */
    protected function isGranted($attributes, $object = null)
    {
        if (!$this->has('securityAuthorizationChecker')) {
            throw new \LogicException('The SecurityBundle is not registered in your application.');
        }
        /** @var AuthorizationCheckerInterface $checker */
        $checker = $this->get('securityAuthorizationChecker');
        return $checker->isGranted($attributes, $object);
    }

    /**
     * Checks the validity of a CSRF token.
     *
     * @param string      $id    The id used when generating the token
     * @param string|null $token The actual token sent with the request that should be validated
     */
    protected function isCsrfTokenValid(string $id, ?string $token): bool
    {
        if (!$this->has('csrfTokenManager')) {
            throw new \LogicException('CSRF protection is not enabled in your application.');
        }

        return $this->get('csrfTokenManager')->isTokenValid(new CsrfToken($id, $token));
    }

    /**
     * Throws an exception unless the attributes are granted against the current authentication token and optionally
     * supplied object.
     *
     * @param mixed  $attributes The attributes
     * @param mixed  $object     The object
     * @param string $message    The message passed to the exception
     *
     * @throws AccessDeniedException
     */
    protected function denyAccessUnlessGranted($attributes, $object = null, $message = 'Access Denied.')
    {
        if (!$this->isGranted($attributes, $object)) {
            $exception = $this->createAccessDeniedException($message);
            throw $exception;
        }
    }
}
