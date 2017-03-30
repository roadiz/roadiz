<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
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
 * @file Controller.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\CMS\Controllers;

use Pimple\Container;
use RZ\Roadiz\Core\ContainerAwareInterface;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\Core\Exceptions\ForceResponseException;
use RZ\Roadiz\Core\Exceptions\NoTranslationAvailableException;
use RZ\Roadiz\Core\ListManagers\EntityListManager;
use RZ\Roadiz\Core\Repositories\TranslationRepository;
use RZ\Roadiz\Utils\ContactFormManager;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Base controller.
 */
abstract class Controller implements ContainerAwareInterface
{
    /**
     * @var Container|null
     */
    protected $container = null;

    /**
     * Shortcut to return the request service.
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->get('request');
    }

    /**
     * Sets the Container associated with this Controller.
     *
     * @param Container $container
     * @return ContainerAwareInterface
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getContainer()
    {
        return $this->container;
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
     * {@inheritdoc}
     */
    public function get($serviceName)
    {
        return $this->container->offsetGet($serviceName);
    }

    /**
     * {@inheritdoc}
     */
    public function has($serviceName)
    {
        return $this->container->offsetExists($serviceName);
    }

    /**
     * Alias for `$this->container['securityAuthorizationChecker']`.
     *
     * @return \Symfony\Component\Security\Core\Authorization\AuthorizationChecker
     */
    public function getAuthorizationChecker()
    {
        return $this->get('securityAuthorizationChecker');
    }

    /**
     * Alias for `$this->container['securityTokenStorage']`.
     *
     * @return \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface
     */
    public function getTokenStorage()
    {
        return $this->get('securityTokenStorage');
    }

    /**
     * Alias for `$this->container['em']`.
     *
     * @return \Doctrine\ORM\EntityManager
     */
    public function em()
    {
        return $this->get('em');
    }

    /**
     * @return \Symfony\Component\Translation\Translator
     */
    public function getTranslator()
    {
        return $this->get('translator');
    }

    /**
     * @return \Twig_Environment
     */
    public function getTwig()
    {
        return $this->get('twig.environment');
    }

    /**
     * Wrap `$this->container['urlGenerator']->generate`
     *
     * @param string|NodesSources $route
     * @param mixed  $parameters
     * @param int $referenceType
     *
     * @return string
     */
    public function generateUrl($route, $parameters = [], $referenceType = Router::ABSOLUTE_PATH)
    {
        return $this->get('urlGenerator')->generate($route, $parameters, $referenceType);
    }

    /**
     * Returns a RedirectResponse to the given URL.
     *
     * @param  string $url
     * @param  integer $status
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirect($url, $status = Response::HTTP_FOUND)
    {
        return new RedirectResponse($url, $status);
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
     *
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
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function removeTrailingSlashAction(Request $request)
    {
        $pathInfo = $request->getPathInfo();
        $requestUri = $request->getRequestUri();

        $url = str_replace($pathInfo, rtrim($pathInfo, ' /'), $requestUri);

        return $this->redirect($url, Response::HTTP_MOVED_PERMANENTLY);
    }

    /**
     * Make translation variable with the good localization.
     *
     * @param Request $request
     * @param string $_locale
     *
     * @return Translation
     * @throws NoTranslationAvailableException
     */
    protected function bindLocaleFromRoute(Request $request, $_locale = null)
    {
        /** @var TranslationRepository $repository */
        $repository = $this->get('em')->getRepository('RZ\Roadiz\Core\Entities\Translation');
        /*
         * If you use a static route for Home page
         * we need to grab manually language.
         *
         * Get language from static route
         */
        if (null !== $_locale) {
            /*
             * First try with override locale
             */
            $translation = $repository->findOneByOverrideLocaleAndAvailable($_locale);

            if ($translation === null) {
                /*
                 * Then with regular locale
                 */
                $translation = $repository->findOneByLocaleAndAvailable($_locale);
            }
            if ($translation === null) {
                throw new NoTranslationAvailableException();
            }
        } else {
            $translation = $this->get('defaultTranslation');
        }

        $request->setLocale($translation->getLocale());
        return $translation;
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
     * @param string $view Template file path
     * @param array $parameters Twig assignation array
     * @param Response $response Optional Response object to customize response parameters
     * @param string $namespace Twig loader namespace
     * @return Response
     * @throws \Twig_Error_Runtime
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

            if ($namespace !== "" && $namespace !== "/") {
                $view = '@' . $namespace . '/' . $view;
            }

            $response->setContent($this->renderView($view, $parameters));
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
        $subRequest = $this->get('request')->duplicate($query, null, $path);
        return $this->get('httpKernel')->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }

    /**
     * Returns a NotFoundHttpException.
     *
     * This will result in a 404 response code. Usage example:
     *
     *     throw $this->createNotFoundException('Page not found!');
     *
     * @param string          $message  A message
     * @param \Exception|null $previous The previous exception
     *
     * @return NotFoundHttpException
     */
    protected function createNotFoundException($message = 'Not Found', \Exception $previous = null)
    {
        return new NotFoundHttpException($message, $previous);
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
     * @param string|FormTypeInterface $type    The built type of the form
     * @param mixed                    $data    The initial data for the form
     * @param array                    $options Options for the form
     *
     * @return Form
     */
    protected function createForm($type, $data = null, array $options = [])
    {
        return $this->get('formFactory')->create($type, $data, $options);
    }

    /**
     * Creates and returns a form builder instance.
     *
     * @param mixed $data    The initial data for the form
     * @param array $options Options for the form
     *
     * @return \Symfony\Component\Form\FormBuilder
     */
    protected function createFormBuilder($data = null, array $options = [])
    {
        return $this->get('formFactory')->createBuilder('form', $data, $options);
    }

    /**
     * Creates and returns an EntityListManager instance.
     *
     * @param mixed $entity Entity class path
     * @param array $criteria
     * @param array $ordering
     *
     * @return EntityListManager
     */
    public function createEntityListManager($entity, array $criteria = [], array $ordering = [])
    {
        return new EntityListManager(
            $this->get('request'),
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
        return new ContactFormManager(
            $this->get('request'),
            $this->get('formFactory'),
            $this->get('translator'),
            $this->get('twig.environment'),
            $this->get('mailer')
        );
    }

    /**
     * Get a user from the tokenStorage.
     *
     * @return User|null
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
        if (null === $token = $this->get('securityTokenStorage')->getToken()) {
            return null;
        }
        if (!is_object($user = $token->getUser())) {
            // e.g. anonymous authentication
            return null;
        }
        return $user;
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
        return $this->get('securityAuthorizationChecker')->isGranted($attributes, $object);
    }
}
