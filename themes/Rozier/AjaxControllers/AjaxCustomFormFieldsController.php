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

use RZ\Roadiz\Core\Entities\CustomFormField;
use Themes\Rozier\AjaxControllers\AbstractAjaxController;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * {@inheritdoc}
 */
class AjaxCustomFormFieldsController extends AbstractAjaxController
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

        if ($field !== null) {

            $responseArray = null;

            /*
             * Get the right update method against "_action" parameter
             */
            switch ($request->get('_action')) {
                case 'updatePosition':
                    $responseArray = $this->updatePosition($request->request->all(), $field);
                    break;
            }

            if ($responseArray === null) {
                $responseArray = array(
                    'statusCode' => '200',
                    'status' => 'success',
                    'responseText' => $this->getTranslator()->trans('field.%name%.updated', array(
                        '%name%' => $field->getName()
                    ))
                );
            }

            return new Response(
                json_encode($responseArray),
                Response::HTTP_OK,
                array('content-type' => 'application/javascript')
            );
        }


        $responseArray = array(
            'statusCode' => '403',
            'status'    => 'danger',
            'responseText' => $this->getTranslator()->trans('field.%customFormFieldId%.not_exists', array(
                '%customFormFieldId%' => $customFormFieldId
            ))
        );

        return new Response(
            json_encode($responseArray),
            Response::HTTP_OK,
            array('content-type' => 'application/javascript')
        );
    }

    /**
     * @param array         $parameters
     * @param CustomFormField $field
     */
    protected function updatePosition($parameters, CustomFormField $field)
    {
        /*
         * First, we set the new parent
         */
        if (!empty($parameters['newPosition']) &&
            null !== $field) {

            $field->setPosition($parameters['newPosition']);
            // Apply position update before cleaning
            $this->getService('em')->flush();

            $field->getHandler()->cleanPositions();
        }
    }
}
