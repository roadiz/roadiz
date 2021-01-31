<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Controllers;

use RZ\Roadiz\Core\Entities\Redirection;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @package RZ\Roadiz\CMS\Controllers
 */
final class RedirectionController
{
    private UrlGeneratorInterface $urlGenerator;

    /**
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @param Request $request
     * @param Redirection $redirection
     * @return RedirectResponse
     */
    public function redirectAction(Request $request, Redirection $redirection)
    {
        if (null !== $redirection->getRedirectNodeSource()) {
            return new RedirectResponse(
                $this->urlGenerator->generate(
                    RouteObjectInterface::OBJECT_BASED_ROUTE_NAME,
                    [RouteObjectInterface::ROUTE_OBJECT => $redirection->getRedirectNodeSource()],
                ),
                $redirection->getType()
            );
        }

        if (null !== $redirection->getRedirectUri() &&
            strlen($redirection->getRedirectUri()) > 0) {
            return new RedirectResponse($redirection->getRedirectUri(), $redirection->getType());
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Redirects to another route with the given name.
     *
     * The response status code is 302 if the permanent parameter is false (default),
     * and 301 if the redirection is permanent.
     *
     * In case the route name is empty, the status code will be 404 when permanent is false
     * and 410 otherwise.
     *
     * @param Request    $request          The request instance
     * @param string     $route            The route name to redirect to
     * @param bool       $permanent        Whether the redirection is permanent
     * @param bool|array $ignoreAttributes Whether to ignore attributes or an array of attributes to ignore
     *
     * @return RedirectResponse A Response instance
     *
     * @throws HttpException In case the route name is empty
     */
    public function redirectToRouteAction(
        Request $request,
        string $route,
        bool $permanent = false,
        $ignoreAttributes = false
    ) {
        if ('' == $route) {
            throw new HttpException($permanent ? 410 : 404);
        }
        $attributes = [];
        if (false === $ignoreAttributes || is_array($ignoreAttributes)) {
            $attributes = $request->attributes->get('_route_params');
            unset($attributes['route'], $attributes['permanent'], $attributes['ignoreAttributes']);
            if ($ignoreAttributes) {
                $attributes = array_diff_key($attributes, array_flip($ignoreAttributes));
            }
        }
        return new RedirectResponse(
            $this->urlGenerator->generate(
                $route,
                $attributes,
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            $permanent ? 301 : 302
        );
    }
}
