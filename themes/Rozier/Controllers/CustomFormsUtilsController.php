<?php
/**
 * Copyright (c) 2016. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file CustomFormsUtilsController.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Entities\CustomForm;
use RZ\Roadiz\Core\Entities\CustomFormAnswer;
use RZ\Roadiz\Utils\XlsxExporter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Themes\Rozier\RozierApp;

/**
 * Class CustomFormsUtilsController
 * @package Themes\Rozier\Controllers
 */
class CustomFormsUtilsController extends RozierApp
{
    /**
     * Export all custom form's answer in a Xlsx file (.rzt).
     *
     * @param Request $request
     * @param int     $customFormId
     *
     * @return Response
     */
    public function exportAction(Request $request, $customFormId)
    {
        /** @var CustomForm $customForm */
        $customForm = $this->get("em")->find(CustomForm::class, $customFormId);
        $answers = $customForm->getCustomFormAnswers();

        /**
         * @var int $key
         * @var CustomFormAnswer $answer
         */
        foreach ($answers as $key => $answer) {
            $array = array_merge(
                [$answer->getIp(), $answer->getSubmittedAt()],
                $answer->toArray()
            );
            $answers[$key] = $array;
        }

        $keys = ["ip", "submitted.date"];

        $fields = $customForm->getFieldsLabels();
        $keys = array_merge($keys, $fields);

        $exporter = new XlsxExporter($this->get('translator'));
        $xlsx = $exporter->exportXlsx($answers, $keys);

        $response = new Response(
            $xlsx,
            Response::HTTP_OK,
            []
        );

        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $customForm->getName() . '.xlsx'
            )
        );

        $response->prepare($request);

        return $response;
    }

    /**
     * Duplicate custom form by ID
     *
     * @param Request $request
     * @param int $customFormId
     *
     * @return Response
     */
    public function duplicateAction(Request $request, $customFormId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_CUSTOMFORMS');

        try {
            $existingCustomForm = $this->get('em')
                ->find(CustomForm::class, (int) $customFormId);

            $newCustomForm = clone $existingCustomForm;

            $em = $this->get("em");

            foreach ($newCustomForm->getFields() as $field) {
                $em->persist($field);
            }

            $em->persist($newCustomForm);

            $em->flush();

            foreach ($newCustomForm->getFields() as $field) {
                $field->setCustomForm($newCustomForm);
            }

            $msg = $this->getTranslator()->trans("duplicated.custom.form.%name%", [
                '%name%' => $existingCustomForm->getDisplayName(),
            ]);

            $this->publishConfirmMessage($request, $msg);

            return $this->redirect($this->get('urlGenerator')
                    ->generate(
                        'customFormsEditPage',
                        ["customFormId" => $newCustomForm->getId()]
                    ));
        } catch (\Exception $e) {
            $request->getSession()->getFlashBag()->add(
                'error',
                $this->getTranslator()->trans("impossible.duplicate.custom.form.%name%", [
                    '%name%' => $existingCustomForm->getDisplayName(),
                ])
            );
            $request->getSession()->getFlashBag()->add('error', $e->getMessage());

            return $this->redirect($this->get('urlGenerator')
                    ->generate(
                        'customFormsEditPage',
                        ["customFormId" => $existingCustomForm->getId()]
                    ));
        }
    }
}
