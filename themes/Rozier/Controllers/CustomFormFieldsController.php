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

use RZ\Roadiz\Core\Entities\CustomFormField;
use Symfony\Component\HttpFoundation\Request;
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

        $customForm = $this->getService('em')
                           ->find('RZ\Roadiz\Core\Entities\CustomForm', (int) $customFormId);

        if ($customForm !== null) {
            $fields = $customForm->getFields();

            $this->assignation['customForm'] = $customForm;
            $this->assignation['fields'] = $fields;

            return $this->render('custom-form-fields/list.html.twig', $this->assignation);
        } else {
            return $this->throw404();
        }
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

        $field = $this->getService('em')
                      ->find('RZ\Roadiz\Core\Entities\CustomFormField', (int) $customFormFieldId);

        if ($field !== null) {
            $this->assignation['customForm'] = $field->getCustomForm();
            $this->assignation['field'] = $field;
            $form = $this->createForm(new CustomFormFieldType(), $field, [
                'em' => $this->getService('em'),
                'customForm' => $field->getCustomForm(),
                'fieldName' => $field->getName(),
            ]);
            $form->handleRequest($request);

            if ($form->isValid()) {
                $this->getService('em')->flush();

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
        } else {
            return $this->throw404();
        }
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
        $customForm = $this->getService('em')
                           ->find('RZ\Roadiz\Core\Entities\CustomForm', $customFormId);
        $field->setCustomForm($customForm);

        if ($customForm !== null &&
            $field !== null) {
            $this->assignation['customForm'] = $customForm;
            $this->assignation['field'] = $field;
            $form = $this->createForm(new CustomFormFieldType(), $field, [
                'em' => $this->getService('em'),
                'customForm' => $customForm,
            ]);
            $form->handleRequest($request);

            if ($form->isValid()) {
                try {
                    $this->getService('em')->persist($field);
                    $this->getService('em')->flush();

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
                    $this->getService('logger')->error($msg);
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
        } else {
            return $this->throw404();
        }
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

        $field = $this->getService('em')
                      ->find('RZ\Roadiz\Core\Entities\CustomFormField', (int) $customFormFieldId);

        if ($field !== null) {
            $this->assignation['field'] = $field;
            $form = $this->buildDeleteForm($field);
            $form->handleRequest($request);

            if ($form->isValid() &&
                $form->getData()['customFormFieldId'] == $field->getId()) {
                $customFormId = $field->getCustomForm()->getId();

                $this->getService('em')->remove($field);
                $this->getService('em')->flush();

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
        } else {
            return $this->throw404();
        }
    }

    /**
     * @param CustomFormField $field
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildDeleteForm(CustomFormField $field)
    {
        $builder = $this->createFormBuilder()
                        ->add('customFormFieldId', 'hidden', [
                            'data' => $field->getId(),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ]);

        return $builder->getForm();
    }
}
