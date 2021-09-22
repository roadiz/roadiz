<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Controllers;

use AM\InterventionRequest\InterventionRequest;
use AM\InterventionRequest\ShortUrlExpander;
use RZ\Roadiz\Core\Models\FileAwareInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Special controller app file for assets management with InterventionRequest lib.
 */
final class InterventionRequestController
{
    private FileAwareInterface $fileAware;
    private InterventionRequest $interventionRequest;

    /**
     * @param FileAwareInterface $fileAware
     * @param InterventionRequest $interventionRequest
     */
    public function __construct(
        FileAwareInterface $fileAware,
        InterventionRequest $interventionRequest
    ) {
        $this->fileAware = $fileAware;
        $this->interventionRequest = $interventionRequest;
    }

    /**
     * @param Request $request
     * @param string  $queryString
     * @param string  $filename
     *
     * @return Response
     */
    public function interventionRequestAction(Request $request, string $queryString, string $filename)
    {
        try {
            /*
             * Handle short url with Url rewriting
             */
            $expander = new ShortUrlExpander($request);
            $expander->setIgnorePath($this->fileAware->getPublicCacheBasePath());
            $expander->injectParamsToRequest($queryString, $filename);

            $this->interventionRequest->handleRequest($request);

            return $this->interventionRequest->getResponse($request);
        } catch (\ReflectionException $e) {
            $message = '[Configuration] ' . $e->getMessage();

            return new Response(
                $message,
                Response::HTTP_INTERNAL_SERVER_ERROR,
                ['content-type' => 'text/plain']
            );
        } catch (\Exception $e) {
            return new Response(
                $e->getMessage(),
                Response::HTTP_NOT_FOUND,
                ['content-type' => 'text/plain']
            );
        }
    }
}
