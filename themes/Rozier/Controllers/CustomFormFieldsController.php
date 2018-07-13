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
 * @file CustomFormFieldsController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Entities\CustomForm;
use RZ\Roadiz\Core\Entities\CustomFormField;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Validator\Constraints\NotBlank;
use Themes\Rozier\Forms\CustomFormFieldType;
use Themes\Rozier\RozierApp;

/**
 * {@inheritdoc}
 */
class CustomFormFieldsController extends RozierApp
{
    /**
     * List every node-type-fields.
     *
     * @param Request $request
     * @param int     $customFormId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(Request $request, $customFormId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_CUSTOMFORMS');

        $customForm = $this->get('em')
                           ->find(CustomForm::class, (int) $customFormId);

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
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $customFormFieldId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_CUSTOMFORMS');

        /** @var CustomFormField $field */
        $field = $this->get('em')
                      ->find(CustomFormField::class, (int) $customFormFieldId);

        if ($field !== null) {
            $this->assignation['customForm'] = $field->getCustomForm();
            $this->assignation['field'] = $field;
            $form = $this->createForm(CustomFormFieldType::class, $field, [
                'em' => $this->get('em'),
                'customForm' => $field->getCustomForm(),
                'fieldName' => $field->getName(),
            ]);
            $form->handleRequest($request);

            if ($form->isValid()) {
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
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request, $customFormId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_CUSTOMFORMS');

        $field = new CustomFormField();
        $customForm = $this->get('em')
                           ->find(CustomForm::class, $customFormId);
        $field->setCustomForm($customForm);

        if ($customForm !== null &&
            $field !== null) {
            $this->assignation['customForm'] = $customForm;
            $this->assignation['field'] = $field;
            $form = $this->createForm(CustomFormFieldType::class, $field, [
                'em' => $this->get('em'),
                'customForm' => $customForm,
            ]);
            $form->handleRequest($request);

            if ($form->isValid()) {
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
                } catch (\Exception $e) {
                    $msg = $e->getMessage();
                    $request->getSession()->getFlashBag()->add('error', $msg);
                    $this->get('logger')->error($msg);
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
     * Return an deletion form for requested node.
     *
     * @param Request $request
     * @param int     $customFormFieldId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $customFormFieldId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_CUSTOMFORMS_DELETE');

        $field = $this->get('em')
                      ->find(CustomFormField::class, (int) $customFormFieldId);

        if ($field !== null) {
            $this->assignation['field'] = $field;
            $form = $this->buildDeleteForm($field);
            $form->handleRequest($request);

            if ($form->isValid() &&
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
     * @return \Symfony\Component\Form\Form
     */
    private function buildDeleteForm(CustomFormField $field)
    {
        $builder = $this->createFormBuilder()
                        ->add('customFormFieldId', HiddenType::class, [
                            'data' => $field->getId(),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ]);

        return $builder->getForm();
    }
}
