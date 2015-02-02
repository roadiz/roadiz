<?php
/**
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
 *
 *
 * @file CustomFormsController.php
 * @author Ambroise Maupate
 */

namespace Themes\Rozier\Controllers;

use \RZ\Roadiz\CMS\Forms\MarkdownType;
use RZ\Roadiz\Core\Entities\CustomForm;
use RZ\Roadiz\Core\ListManagers\EntityListManager;
use Themes\Rozier\RozierApp;

use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;

/**
* CustomForm controller
*/
class CustomFormsController extends RozierApp
{
    /**
     * List every node-types.
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_CUSTOMFORMS');
        /*
         * Manage get request to filter list
         */
        $listManager = new EntityListManager(
            $request,
            $this->getService('em'),
            'RZ\Roadiz\Core\Entities\CustomForm'
        );
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['custom_forms'] = $listManager->getEntities();

        return new Response(
            $this->getTwig()->render('custom-forms/list.html.twig', $this->assignation),
            Response::HTTP_OK,
            ['content-type' => 'text/html']
        );
    }

    /**
     * Return an edition form for requested node-type.
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $customFormId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $customFormId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_CUSTOMFORMS');

        $customForm = $this->getService('em')
            ->find('RZ\Roadiz\Core\Entities\CustomForm', (int) $customFormId);

        if (null !== $customForm) {
            $this->assignation['customForm'] = $customForm;

            $form = $this->buildForm($customForm);

            $form->handleRequest();

            if ($form->isValid()) {
                try {
                    $this->editCustomForm($form->getData(), $customForm);

                    $msg = $this->getTranslator()->trans('customForm.%name%.updated', ['%name%'=>$customForm->getName()]);
                    $this->publishConfirmMessage($request, $msg);
                } catch (EntityAlreadyExistsException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                }
                /*
                 * Redirect to update schema page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate(
                        'customFormsHomePage',
                        [
                            '_token' => $this->getService('csrfProvider')->generateCsrfToken(static::SCHEMA_TOKEN_INTENTION)
                        ]
                    )
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('custom-forms/edit.html.twig', $this->assignation),
                Response::HTTP_OK,
                ['content-type' => 'text/html']
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return an creation form for requested node-type.
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_CUSTOMFORMS');

        $customForm = new CustomForm();

        if (null !== $customForm) {
            $this->assignation['customForm'] = $customForm;

            /*
             * form
             */
            $form = $this->buildForm($customForm);
            $form->handleRequest();
            if ($form->isValid()) {
                try {
                    $this->addCustomForm($form->getData(), $customForm);

                    $msg = $this->getTranslator()->trans('customForm.%name%.created', ['%name%'=>$customForm->getName()]);
                    $this->publishConfirmMessage($request, $msg);

                    /*
                     * Redirect to update schema page
                     */
                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'customFormsHomePage'
                        )
                    );

                } catch (EntityAlreadyExistsException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'customFormsAddPage'
                        )
                    );
                }
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('custom-forms/add.html.twig', $this->assignation),
                Response::HTTP_OK,
                ['content-type' => 'text/html']
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return an deletion form for requested node-type.
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $customFormId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $customFormId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_CUSTOMFORMS_DELETE');

        $customForm = $this->getService('em')
            ->find('RZ\Roadiz\Core\Entities\CustomForm', (int) $customFormId);

        if (null !== $customForm) {
            $this->assignation['customForm'] = $customForm;

            $form = $this->buildDeleteForm($customForm);

            $form->handleRequest();

            if ($form->isValid() &&
                $form->getData()['customFormId'] == $customForm->getId() ) {
                $this->getService("em")->remove($customForm);

                $msg = $this->getTranslator()->trans('customForm.%name%.deleted', ['%name%'=>$customForm->getName()]);
                $this->publishConfirmMessage($request, $msg);
                /*
                 * Redirect to update schema page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate(
                        'customFormsHomePage'
                    )
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('custom-forms/delete.html.twig', $this->assignation),
                Response::HTTP_OK,
                ['content-type' => 'text/html']
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * @param array                           $data
     * @param RZ\Roadiz\Core\Entities\CustomForm $customForm
     *
     * @return boolean
     */
    private function editCustomForm($data, CustomForm $customForm)
    {
        foreach ($data as $key => $value) {
            if (isset($data['name'])) {
                throw new EntityAlreadyExistsException($this->getTranslator()->trans('customForm.%name%.cannot_rename_already_exists', ['%name%'=>$customForm->getName()]), 1);
            }
            $setter = 'set'.ucwords($key);
            $customForm->$setter( $value );
        }

        $this->getService('em')->flush();

        return true;
    }

    /**
     * @param array                           $data
     * @param RZ\Roadiz\Core\Entities\CustomForm $customForm
     *
     * @return boolean
     */
    private function addCustomForm($data, CustomForm $customForm)
    {
        foreach ($data as $key => $value) {
            $setter = 'set'.ucwords($key);
            if ($key == "displayName") {
                $customForm->setName($value);
            }
            $customForm->$setter($value);
        }

        $existing = $this->getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\CustomForm')
            ->findOneBy(['name'=>$customForm->getName()]);
        if ($existing !== null) {
            throw new EntityAlreadyExistsException($this->getTranslator()->trans('customForm.%name%.already_exists', ['%name%'=>$customForm->getName()]), 1);
        }

        $this->getService('em')->persist($customForm);
        $this->getService('em')->flush();

        return true;
    }

    /**
     * @param RZ\Roadiz\Core\Entities\CustomForm $customForm
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildForm(CustomForm $customForm)
    {
        $defaults = [
            'displayName' =>    $customForm->getDisplayName(),
            'description' =>    $customForm->getDescription(),
            'email' =>    $customForm->getEmail(),
            'open' =>           $customForm->isOpen(),
            'closeDate' =>      $customForm->getCloseDate(),
            'color' =>          $customForm->getColor(),
        ];
        $builder = $this->getService('formFactory')
            ->createBuilder('form', $defaults)
            ->add('displayName', 'text', [
                'label' => $this->getTranslator()->trans('customForm.displayName'),
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('description', new MarkdownType(), [
                'label' => $this->getTranslator()->trans('description'),
                'required' => false
            ])
            ->add('email', 'email', [
                'label' => $this->getTranslator()->trans('email'),
                'required' => false,
                'constraints' => [
                    new Email()
                ]
            ])
            ->add('open', 'checkbox', [
                'label' => $this->getTranslator()->trans('customForm.open'),
                'required' => false
            ])
            ->add('closeDate', 'datetime', [
                'label' => $this->getTranslator()->trans('customForm.closeDate'),
                'required' => false
            ])
            ->add('color', 'text', [
                'label' => $this->getTranslator()->trans('customForm.color'),
                'required' => false,
                'attr' => ['class'=>'colorpicker-input']
            ]);

        return $builder->getForm();
    }

    /**
     * @param RZ\Roadiz\Core\Entities\CustomForm $customForm
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildDeleteForm(CustomForm $customForm)
    {
        $builder = $this->getService('formFactory')
            ->createBuilder('form')
            ->add('customFormId', 'hidden', [
                'data' => $customForm->getId(),
                'constraints' => [
                    new NotBlank()
                ]
            ]);

        return $builder->getForm();
    }
}
