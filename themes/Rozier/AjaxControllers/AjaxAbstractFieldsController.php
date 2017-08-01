<?php
/*
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
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
 *
 * @file AjaxCustomFormFieldsController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\AjaxControllers;

use RZ\Roadiz\Core\AbstractEntities\AbstractField;
use RZ\Roadiz\Core\Entities\CustomFormField;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Handlers\CustomFormFieldHandler;
use RZ\Roadiz\Core\Handlers\NodeTypeFieldHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * {@inheritdoc}
 */
class AjaxAbstractFieldsController extends AbstractAjaxController
{
    /**
     * Handle actions for any abstract fields.
     *
     * @param Request       $request
     * @param AbstractField $field
     *
     * @return null|Response
     */
    protected function handleFieldActions(Request $request, AbstractField $field = null)
    {
        /*
         * Validate
         */
        if (true !== $notValid = $this->validateRequest($request)) {
            return new JsonResponse(
                $notValid,
                Response::HTTP_FORBIDDEN
            );
        }

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
                $responseArray = [
                    'statusCode' => '200',
                    'status' => 'success',
                    'responseText' => $this->getTranslator()->trans('field.%name%.updated', [
                        '%name%' => $field->getName(),
                    ]),
                ];
            }

            return new JsonResponse(
                $responseArray,
                Response::HTTP_OK
            );
        }

        return null;
    }

    /**
     * @param array         $parameters
     * @param AbstractField $field
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
            $this->get('em')->flush();
            if ($field instanceof NodeTypeField) {
                /** @var NodeTypeFieldHandler $handler */
                $handler = $this->get('node_type_field.handler');
                $handler->setNodeTypeField($field);
                $handler->cleanPositions();
            } elseif ($field instanceof CustomFormField) {
                /** @var CustomFormFieldHandler $handler */
                $handler = $this->get('custom_form_field.handler');
                $handler->setCustomFormField($field);
                $handler->cleanPositions();
            }
        }
    }
}
