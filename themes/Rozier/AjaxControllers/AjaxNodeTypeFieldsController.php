<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file AjaxNodeTypeFieldsController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\AjaxControllers;

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Handlers\NodeHandler;
use Themes\Rozier\AjaxControllers\AbstractAjaxController;
use Themes\Rozier\RozierApp;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * {@inheritdoc}
 */
class AjaxNodeTypeFieldsController extends AbstractAjaxController
{
    /**
     * Handle AJAX edition requests for NodeTypeFields
     * such as coming from widgets.
     *
     * @param Request $request
     * @param int     $nodeTypeFieldId
     *
     * @return Symfony\Component\HttpFoundation\Response JSON response
     */
    public function editAction(Request $request, $nodeTypeFieldId)
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

        $this->validateAccessForRole('ROLE_ACCESS_NODEFIELDS_DELETE');

        $field = $this->getService('em')
                      ->find('RZ\Roadiz\Core\Entities\NodeTypeField', (int) $nodeTypeFieldId);

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
            'responseText' => $this->getTranslator()->trans('field.%nodeTypeFieldId%.not_exists', array(
                '%nodeTypeFieldId%' => $nodeTypeFieldId
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
     * @param NodeTypeField $field
     */
    protected function updatePosition($parameters, NodeTypeField $field)
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
