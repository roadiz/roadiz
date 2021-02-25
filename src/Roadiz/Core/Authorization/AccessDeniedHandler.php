<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Authorization;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

/**
 * This is used by the ExceptionListener to translate an AccessDeniedException
 * to a Response object.
 */
class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    protected LoggerInterface $logger;
    protected UrlGeneratorInterface $urlGenerator;
    protected string $redirectRoute;
    protected array $redirectParameters;

    /**
     * @param UrlGeneratorInterface $urlGenerator
     * @param LoggerInterface $logger
     * @param string $redirectRoute Route to redirect if access denied is thrown
     * @param array $redirectParameters
     */
    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        LoggerInterface $logger,
        $redirectRoute = '',
        $redirectParameters = []
    ) {
        $this->logger = $logger;
        $this->urlGenerator = $urlGenerator;
        $this->redirectRoute = $redirectRoute;
        $this->redirectParameters = $redirectParameters;
    }

    /**
     * Handles an access denied failure redirecting to home page
     *
     * @param Request $request
     * @param AccessDeniedException $accessDeniedException
     *
     * @return Response may return null
     */
    public function handle(Request $request, AccessDeniedException $accessDeniedException)
    {
        $this->logger->error('User tried to access: ' . $request->getUri());

        $returnJson = $request->isXmlHttpRequest() ||
            $request->getRequestFormat() === 'json' ||
            (
                count($request->getAcceptableContentTypes()) === 1 &&
                $request->getAcceptableContentTypes()[0] === 'application/json'
            ) ||
            ($request->attributes->has('_format') && $request->attributes->get('_format') === 'json');

        if ($returnJson) {
            return new JsonResponse(
                [
                    'message' => $accessDeniedException->getMessage(),
                    'trace' => $accessDeniedException->getTraceAsString(),
                    'exception' => get_class($accessDeniedException),
                ],
                Response::HTTP_FORBIDDEN
            );
        } else {
            if ('' !== $this->redirectRoute) {
                $redirectUrl = $this->urlGenerator->generate($this->redirectRoute, $this->redirectParameters);
            } else {
                $redirectUrl = $request->getBaseUrl();
            }
            // Forbidden code should be set on final response, not the redirection!
            return new RedirectResponse($redirectUrl, Response::HTTP_FOUND);
        }
    }
}
