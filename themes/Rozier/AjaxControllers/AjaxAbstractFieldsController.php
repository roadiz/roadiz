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
        /*
         * Validate
         */
        if (true !== $notValid = $this->validateRequest($request)) {
            return new Response(
                json_encode($notValid),
                Response::HTTP_OK,
                ['content-type' => 'application/javascript']
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
                        '%name%' => $field->getName()
                    ])
                ];
            }

            return new Response(
                json_encode($responseArray),
                Response::HTTP_OK,
                ['content-type' => 'application/javascript']
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
