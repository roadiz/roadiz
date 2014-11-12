<?php

namespace Themes\Rozier\Controllers;

use Themes\Rozier\RozierApp;
use RZ\Renzo\Core\Utils\XlsxExporter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * {@inheritdoc}
 */
class CustomFormsUtilsController extends RozierApp
{
    /**
     * Export all custom form's answer in a Xlsx file (.rzt).
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function exportAction(Request $request, $customFormId)
    {
        $customForm = $this->getService("em")->find("RZ\Renzo\Core\Entities\CustomForm", $customFormId);

        $answers = $customForm->getCustomFormAnswers();


        foreach ($answers as $key => $answer) {
            $array = array($answer->getIp(), $answer->getSubmittedAt());
            foreach ($answer->getAnswers() as $obj) {
                $array[] = $obj->getValue();
            }
            $answers[$key] = $array;
        }

        $keys = array("ip", "submittedDate");

        $fields = $customForm->getFieldsLabels();

        $keys = array_merge($keys, $fields);

        $xlsx = XlsxExporter::exportXlsx($answers, $keys);

        $response =  new Response(
            $xlsx,
            Response::HTTP_OK,
            array()
        );

        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                'export.xlsx'
            )
        ); // Rezo-Zero Type

        $response->prepare($request);

        return $response;
    }
}