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

use RZ\Roadiz\Core\AbstractEntities\AbstractField;
use Themes\Rozier\AjaxControllers\AbstractAjaxController;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * {@inheritdoc}
 */
class AjaxAbstractFieldsController extends AbstractAjaxController
{
    /**
     * Handle actions for any abstract fields.
     *
     * @param AbstractField $field
     *
     * @return Response|null
     */
    protected function handleFieldActions(Request $request, AbstractField $field = null)
    {
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

        return null;
    }

    /**
     * @param array         $parameters
     * @param CustomFormField $field
     */
    protected function updatePosition($parameters, AbstractField $field = null)
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
