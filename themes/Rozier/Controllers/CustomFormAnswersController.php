<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Entities\CustomForm;
use RZ\Roadiz\Core\Entities\CustomFormAnswer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Validator\Constraints\NotBlank;
use Themes\Rozier\RozierApp;

/**
 * Class CustomFormAnswersController
 *
 * @package Themes\Rozier\Controllers
 */
class CustomFormAnswersController extends RozierApp
{
    /**
     * List every node-types.
     *
     * @param Request $request
     * @param int     $customFormId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Twig_Error_Runtime
     */
    public function listAction(Request $request, $customFormId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_CUSTOMFORMS');
        /*
         * Manage get request to filter list
         */

        $customForm = $this->get('em')->find(
            CustomForm::class,
            $customFormId
        );

        $listManager = $this->createEntityListManager(
            CustomFormAnswer::class,
            ["customForm" => $customForm],
            ["submittedAt" => "DESC"]
        );
        $listManager->setDisplayingNotPublishedNodes(true);
        $listManager->handle();
        $this->assignation['customForm'] = $customForm;
        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['custom_form_answers'] = $listManager->getEntities();

        return $this->render('custom-form-answers/list.html.twig', $this->assignation);
    }

    /**
     * Return an deletion form for requested node-type.
     *
     * @param Request $request
     * @param int $customFormAnswerId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $customFormAnswerId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_CUSTOMFORMS_DELETE');

        $customFormAnswer = $this->get('em')
                                 ->find(CustomFormAnswer::class, (int) $customFormAnswerId);

        if (null !== $customFormAnswer) {
            $this->assignation['customFormAnswer'] = $customFormAnswer;

            $form = $this->buildDeleteForm($customFormAnswer);

            $form->handleRequest($request);

            if ($form->isSubmitted() &&
                $form->isValid() &&
                $form->getData()['customFormAnswerId'] == $customFormAnswer->getId()) {
                $this->get("em")->remove($customFormAnswer);

                $msg = $this->getTranslator()->trans('customFormAnswer.%id%.deleted', ['%id%' => $customFormAnswer->getId()]);
                $this->publishConfirmMessage($request, $msg);
                /*
                 * Redirect to update schema page
                 */
                return $this->redirect($this->generateUrl(
                    'customFormAnswersHomePage',
                    ["customFormId" => $customFormAnswer->getCustomForm()->getId()]
                ));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('custom-form-answers/delete.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * @param CustomFormAnswer $customFormAnswer
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildDeleteForm(CustomFormAnswer $customFormAnswer)
    {
        $builder = $this->createFormBuilder()
                        ->add('customFormAnswerId', HiddenType::class, [
                            'data' => $customFormAnswer->getId(),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ]);

        return $builder->getForm();
    }
}
