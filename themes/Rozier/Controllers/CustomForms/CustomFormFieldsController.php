<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers\CustomForms;

use Exception;
use RZ\Roadiz\Core\Entities\CustomForm;
use RZ\Roadiz\Core\Entities\CustomFormField;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Themes\Rozier\Forms\CustomFormFieldType;
use Themes\Rozier\RozierApp;

/**
 * @package Themes\Rozier\Controllers
 */
class CustomFormFieldsController extends RozierApp
{
    /**
     * List every node-type-fields.
     *
     * @param Request $request
     * @param int     $customFormId
     *
     * @return Response
     */
    public function listAction(Request $request, int $customFormId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_CUSTOMFORMS');

        $customForm = $this->get('em')->find(CustomForm::class, $customFormId);

        if ($customForm !== null) {
            $fields = $customForm->getFields();

            $this->assignation['customForm'] = $customForm;
            $this->assignation['fields'] = $fields;

            return $this->render('custom-form-fields/list.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Return an edition form for requested node-type.
     *
     * @param Request $request
     * @param int     $customFormFieldId
     *
     * @return Response
     */
    public function editAction(Request $request, int $customFormFieldId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_CUSTOMFORMS');

        /** @var CustomFormField|null $field */
        $field = $this->get('em')->find(CustomFormField::class, $customFormFieldId);

        if ($field !== null) {
            $this->assignation['customForm'] = $field->getCustomForm();
            $this->assignation['field'] = $field;
            $form = $this->createForm(CustomFormFieldType::class, $field);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $this->get('em')->flush();

                $msg = $this->getTranslator()->trans('customFormField.%name%.updated', ['%name%' => $field->getName()]);
                $this->publishConfirmMessage($request, $msg);

                /*
                 * Redirect to update schema page
                 */
                return $this->redirect($this->generateUrl(
                    'customFormFieldsListPage',
                    [
                        'customFormId' => $field->getCustomForm()->getId(),
                    ]
                ));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('custom-form-fields/edit.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Return an creation form for requested node-type.
     *
     * @param Request $request
     * @param int     $customFormId
     *
     * @return Response
     */
    public function addAction(Request $request, int $customFormId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_CUSTOMFORMS');

        $field = new CustomFormField();
        $customForm = $this->get('em')->find(CustomForm::class, $customFormId);
        $field->setCustomForm($customForm);

        if ($customForm !== null &&
            $field !== null) {
            $this->assignation['customForm'] = $customForm;
            $this->assignation['field'] = $field;
            $form = $this->createForm(CustomFormFieldType::class, $field);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $this->get('em')->persist($field);
                    $this->get('em')->flush();

                    $msg = $this->getTranslator()->trans(
                        'customFormField.%name%.created',
                        ['%name%' => $field->getName()]
                    );
                    $this->publishConfirmMessage($request, $msg);

                    /*
                     * Redirect to update schema page
                     */
                    return $this->redirect($this->generateUrl(
                        'customFormFieldsListPage',
                        [
                            'customFormId' => $customFormId,
                        ]
                    ));
                } catch (Exception $e) {
                    $msg = $e->getMessage();
                    $this->publishErrorMessage($request, $msg);
                    /*
                     * Redirect to add page
                     */
                    return $this->redirect($this->generateUrl(
                        'customFormFieldsAddPage',
                        ['customFormId' => $customFormId]
                    ));
                }
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('custom-form-fields/add.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Return a deletion form for requested node.
     *
     * @param Request $request
     * @param int     $customFormFieldId
     *
     * @return Response
     */
    public function deleteAction(Request $request, int $customFormFieldId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_CUSTOMFORMS_DELETE');

        $field = $this->get('em')->find(CustomFormField::class, $customFormFieldId);

        if ($field !== null) {
            $this->assignation['field'] = $field;
            $form = $this->buildDeleteForm($field);
            $form->handleRequest($request);

            if ($form->isSubmitted() &&
                $form->isValid() &&
                $form->getData()['customFormFieldId'] == $field->getId()) {
                $customFormId = $field->getCustomForm()->getId();

                $this->get('em')->remove($field);
                $this->get('em')->flush();

                /*
                 * Update Database
                 */
                $msg = $this->getTranslator()->trans(
                    'customFormField.%name%.deleted',
                    ['%name%' => $field->getName()]
                );
                $this->publishConfirmMessage($request, $msg);

                /*
                 * Redirect to update schema page
                 */
                return $this->redirect($this->generateUrl(
                    'customFormFieldsListPage',
                    [
                        'customFormId' => $customFormId,
                    ]
                ));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('custom-form-fields/delete.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * @param CustomFormField $field
     *
     * @return FormInterface
     */
    private function buildDeleteForm(CustomFormField $field)
    {
        $builder = $this->createFormBuilder()
                        ->add('customFormFieldId', HiddenType::class, [
                            'data' => $field->getId(),
                            'constraints' => [
                                new NotNull(),
                                new NotBlank(),
                            ],
                        ]);

        return $builder->getForm();
    }
}
