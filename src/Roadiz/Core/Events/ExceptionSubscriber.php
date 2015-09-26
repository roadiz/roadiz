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
 * @file ExceptionSubscriber.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Events;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use RZ\Roadiz\Core\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use RZ\Roadiz\Core\Exceptions\MaintenanceModeException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ExceptionSubscriber implements EventSubscriberInterface
{
    protected $logger;
    protected $debug;

    public function __construct(LoggerInterface $logger, $debug = false)
    {
        $this->logger = $logger;
        $this->debug = $debug;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        // You get the exception object from the received event
        $exception = $event->getException();

        if ($exception instanceof MaintenanceModeException &&
            null !== $ctrl = $exception->getController()) {
            $response = $ctrl->maintenanceAction($event->getRequest());
            $response->setStatusCode(Response::HTTP_SERVICE_UNAVAILABLE);
            $event->setResponse($response);
        } else {
            // Customize your response object to display the exception details
            $response = $this->getEmergencyResponse($exception, $event->getRequest());

            // HttpExceptionInterface is a special type of exception that
            // holds status code and header details
            if ($exception instanceof HttpExceptionInterface) {
                $response->setStatusCode($exception->getStatusCode());
                $response->headers->replace($exception->getHeaders());
            } elseif ($exception instanceof AccessDeniedException) {
                $response->setStatusCode(Response::HTTP_FORBIDDEN);
            } else {
                $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Send the modified response object to the event
            $event->setResponse($response);
        }
    }

    /**
     * Create an emergency response to be sent instead of error logs.
     *
     * @param \Exception $e
     *
     * @return Response
     */
    protected function getEmergencyResponse(\Exception $e, Request $request)
    {
        /*
         * Log error before displaying a fallback page.
         */
        $class = get_class($e);
        $humanMessage = $this->getHumanExceptionTitle($e);

        $this->logger->emerg($e->getMessage(), [
            'trace' => $e->getTraceAsString(),
            'exception' => $class,
        ]);

        if ($request->isXmlHttpRequest()) {
            return new \Symfony\Component\HttpFoundation\JsonResponse(
                [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'exception' => $class,
                    'humanMessage' => $humanMessage,
                ],
                Response::HTTP_SERVICE_UNAVAILABLE
            );
        } else {
            $html = file_get_contents(ROADIZ_ROOT . '/src/Roadiz/CMS/Resources/views/emerg.html');
            $html = str_replace('{{ humanMessage }}', $humanMessage, $html);
            $html = str_replace('{{ message }}', $e->getMessage(), $html);

            if ($this->debug) {
                $trace = preg_replace('#([^\n]+)#', '<p>$1</p>', $e->getTraceAsString());
                $html = str_replace('{{ details }}', $trace, $html);
            } else {
                $html = str_replace('{{ details }}', '', $html);
            }

            return new Response(
                $html,
                Response::HTTP_SERVICE_UNAVAILABLE,
                ['content-type' => 'text/html']
            );
        }
    }

    protected function getHumanExceptionTitle(\Exception $e)
    {
        if ($e instanceof \Doctrine\DBAL\Exception\TableNotFoundException) {
            return "Your database is not synchronised to Roadiz data schema. Did you run install before using Roadiz?";
        }

        if ($e instanceof AccessDeniedException) {
            return "Oups! Wrong way, you are not supposed to be here.";
        }

        return "A problem occured on our website. We are working on this to be back soon.";
    }
}
