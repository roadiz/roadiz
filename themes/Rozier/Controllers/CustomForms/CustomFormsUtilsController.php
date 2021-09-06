<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers\CustomForms;

use RZ\Roadiz\Core\Entities\CustomForm;
use RZ\Roadiz\Core\Entities\CustomFormAnswer;
use RZ\Roadiz\Utils\CustomForm\CustormFormAnswerSerializer;
use RZ\Roadiz\Utils\XlsxExporter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Themes\Rozier\RozierApp;

/**
 * @package Themes\Rozier\Controllers
 */
class CustomFormsUtilsController extends RozierApp
{
    /**
     * Export all custom form's answer in a Xlsx file (.rzt).
     *
     * @param Request $request
     * @param int     $id
     *
     * @return Response
     */
    public function exportAction(Request $request, int $id)
    {
        /** @var CustomForm $customForm */
        $customForm = $this->get("em")->find(CustomForm::class, $id);
        /** @var CustormFormAnswerSerializer $serializer */
        $serializer = $this->get(CustormFormAnswerSerializer::class);
        $answers = $customForm->getCustomFormAnswers();

        /**
         * @var int $key
         * @var CustomFormAnswer $answer
         */
        foreach ($answers as $key => $answer) {
            $array = array_merge(
                [$answer->getIp(), $answer->getSubmittedAt()],
                $serializer->toSimpleArray($answer)
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
     * @param int $id
     *
     * @return Response
     */
    public function duplicateAction(Request $request, int $id)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_CUSTOMFORMS');
        /** @var CustomForm|null $existingCustomForm */
        $existingCustomForm = $this->get('em')->find(CustomForm::class, $id);

        if (null === $existingCustomForm) {
            throw $this->createNotFoundException();
        }

        try {
            $newCustomForm = clone $existingCustomForm;
            $newCustomForm->setCreatedAt(new \DateTime());
            $newCustomForm->setUpdatedAt(new \DateTime());
            $em = $this->get("em");

            foreach ($newCustomForm->getFields() as $field) {
                $em->persist($field);
            }

            $em->persist($newCustomForm);
            $em->flush();

            $msg = $this->getTranslator()->trans("duplicated.custom.form.%name%", [
                '%name%' => $existingCustomForm->getDisplayName(),
            ]);

            $this->publishConfirmMessage($request, $msg);

            return $this->redirect($this->get('urlGenerator')
                    ->generate(
                        'customFormsEditPage',
                        ["id" => $newCustomForm->getId()]
                    ));
        } catch (\Exception $e) {
            $this->publishErrorMessage(
                $request,
                $this->getTranslator()->trans("impossible.duplicate.custom.form.%name%", [
                    '%name%' => $existingCustomForm->getDisplayName(),
                ])
            );
            $this->publishErrorMessage($request, $e->getMessage());

            return $this->redirect($this->get('urlGenerator')
                    ->generate(
                        'customFormsEditPage',
                        ["id" => $existingCustomForm->getId()]
                    ));
        }
    }
}
