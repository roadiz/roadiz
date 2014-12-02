<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file AjaxCustomFormFieldsController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\AjaxControllers;

use Themes\Rozier\AjaxControllers\AjaxAbstractFieldsController;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * {@inheritdoc}
 */
class AjaxCustomFormFieldsController extends AjaxAbstractFieldsController
{
    /**
     * Handle AJAX edition requests for CustomFormFields
     * such as coming from widgets.
     *
     * @param Request $request
     * @param int     $customFormFieldId
     *
     * @return Symfony\Component\HttpFoundation\Response JSON response
     */
    public function editAction(Request $request, $customFormFieldId)
    {
        /*
         * Validate
         */
        if (true !== $notValid = $this->validateRequest($request)) {
            return new Response(
                json_encode($notValid),
                Response::HTTP_FORBIDDEN,
                array('content-type' => 'application/javascript')
            );
        }

        $this->validateAccessForRole('ROLE_ACCESS_CUSTOMFORMS_DELETE');

        $field = $this->getService('em')
                      ->find('RZ\Roadiz\Core\Entities\CustomFormField', (int) $customFormFieldId);

        if (null !== $response = $this->handleFieldActions($request, $field)) {
            return $response;
        }

        $responseArray = array(
            'statusCode' => '403',
            'status'    => 'danger',
            'responseText' => $this->getTranslator()->trans(
                'field.%customFormFieldId%.not_exists',
                array(
                    '%customFormFieldId%' => $customFormFieldId
                )
            )
        );

        return new Response(
            json_encode($responseArray),
            Response::HTTP_OK,
            array('content-type' => 'application/javascript')
        );
    }
}
