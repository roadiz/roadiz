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
use RZ\Roadiz\Core\Exceptions\NoTranslationAvailableException;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Base controller.
 */
class Controller
{
    protected $container = null;
    protected $kernel = null;

    /**
     * Shortcut to return the request service.
     *
     * @return Symfony\Component\HttpFoundation\Request
     */
    public function getRequest()
    {
        return $this->container['request'];
    }

    /**
     * Sets the Container associated with this Controller.
     *
     * @param Pimple\Container $container
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Get general dependency injection container.
     *
     * @return Pimple\Container
     */
    public function getContainer()
    {
        return $this->container;
    }

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
     * *Alias for `$this->container[$key]`*
     *
     * @param string|null $key
     *
     * @return mixed
     */
    public function getService($key = null)
    {
        return $this->container[$key];
    }

    /**
     * Alias for `$this->container['securityContext']`.
     *
     * @return Symfony\Component\Security\Core\SecurityContext
     */
    public function getSecurityContext()
    {
        return $this->container['securityContext'];
    }

    /**
     * Alias for `$this->container['em']`.
     *
     * @return Doctrine\ORM\EntityManager
     */
    public function em()
    {
        return $this->container['em'];
    }

    /**
     * @return Symfony\Component\Translation\Translator
     */
    public function getTranslator()
    {
        return $this->container['translator'];
    }

    /**
     * @return \Twig_Environment
     */
    public function getTwig()
    {
        return $this->container['twig.environment'];
    }

    /**
     * Wrap `$this->container['urlGenerator']->generate`
     *
     * @param  string  $route
     * @param  mixed  $parameters
     * @param  boolean $absolute
     *
     * @return string
     */
    public function generateUrl($route, $parameters = [], $absolute = false)
    {
        return $this->container['urlGenerator']->generate($route, $parameters, $absolute);
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
     * Validate a request against a given ROLE_* and throws
     * an AccessDeniedException exception.
     *
     * @param string $role
     *
     * @throws AccessDeniedException
     */
    public function validateAccessForRole($role)
    {
        if (!$this->container['securityContext']->isGranted($role)) {
            throw new AccessDeniedException("You don't have access to this page:" . $role);
        }
    }

    /**
     * Custom route for redirecting routes with a trailing slash.
     *
     * @param  Request $request
     *
     * @return Symfony\Component\HttpFoundation\RedirectResponse
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
            $translation = $this->container['em']
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
            $translation = $this->container['em']
                                ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                                ->findDefault();
            $request->setLocale($translation->getLocale());
        }
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
        return $this->container['twig.environment']->render($view, $parameters);
    }

    /**
     * Return a Response from a template string with its rendering assignation.
     *
     * @see http://api.symfony.com/2.6/Symfony/Bundle/FrameworkBundle/Controller/Controller.html#method_render
     *
     * @param  string        $view       Template file path
     * @param  array         $parameters Twig assignation array
     * @param  Symfony\Component\HttpFoundation\Response|null $response Optional Response object to customize response parameters
     * @param  string        $namespace  Twig loader namespace
     *
     * @return Symfony\Component\HttpFoundation\Response
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
        }

        $response->setContent($this->renderView($view, $parameters));

        return $response;
    }
}
