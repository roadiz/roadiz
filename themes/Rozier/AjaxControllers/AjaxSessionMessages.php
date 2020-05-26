<?php
declare(strict_types=1);

namespace Themes\Rozier\AjaxControllers;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * AjaxSessionMessages.
 */
class AjaxSessionMessages extends AbstractAjaxController
{
    /**
     * @param Request $request
     *
     * @return Response JSON response
     */
    public function getMessagesAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_BACKEND_USER');

        $responseArray = [
            'statusCode' => Response::HTTP_OK,
            'status'    => 'success'
        ];

        if ($request->hasPreviousSession()) {
            $responseArray['messages'] = $request->getSession()->getFlashBag()->all();
        }
        return new JsonResponse(
            $responseArray
        );
    }
}
