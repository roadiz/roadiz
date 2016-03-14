<?php

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Utils\XlsxExporter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Themes\Rozier\RozierApp;

/**
 * {@inheritdoc}
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
        $customForm = $this->getService("em")->find("RZ\Roadiz\Core\Entities\CustomForm", $customFormId);

        $answers = $customForm->getCustomFormAnswers();

        foreach ($answers as $key => $answer) {
            $array = [$answer->getIp(), $answer->getSubmittedAt()];
            foreach ($answer->getAnswers() as $obj) {
                $array[] = $obj->getValue();
            }
            $answers[$key] = $array;
        }

        $keys = ["ip", "submittedDate"];

        $fields = $customForm->getFieldsLabels();

        $keys = array_merge($keys, $fields);

        $xlsx = XlsxExporter::exportXlsx($answers, $keys);

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
     * @param int     $customFormId
     *
     * @return Response
     */
    public function duplicateAction(Request $request, $customFormId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_CUSTOMFORMS');

        try {
            $existingCustomForm = $this->getService('em')
                ->find('RZ\Roadiz\Core\Entities\CustomForm', (int) $customFormId);

            $newCustomForm = clone $existingCustomForm;

            $em = $this->getService("em");

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

            return $this->redirect($this->getService('urlGenerator')
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

            return $this->redirect($this->getService('urlGenerator')
                    ->generate(
                        'customFormsEditPage',
                        ["customFormId" => $existingCustomForm->getId()]
                    ));
        }
    }
}
