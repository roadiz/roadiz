<?php
/**
 * Copyright REZO ZERO 2014
 *
 *
 *
 *
 * @file CustomFormsController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */

namespace Themes\Rozier\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\CustomForm;
use RZ\Renzo\Core\Entities\CustomFormField;
use RZ\Renzo\Core\Entities\CustomFormFieldAttribute;
use RZ\Renzo\Core\Entities\CustomFormAnswer;
use RZ\Renzo\Core\ListManagers\EntityListManager;
use Themes\Rozier\RozierApp;

use RZ\Renzo\Core\Exceptions\EntityAlreadyExistsException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use \Symfony\Component\Form\Form;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Type;

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
            'RZ\Renzo\Core\Entities\CustomForm'
        );
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['custom_forms'] = $listManager->getEntities();

        return new Response(
            $this->getTwig()->render('custom-forms/list.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
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
            ->find('RZ\Renzo\Core\Entities\CustomForm', (int) $customFormId);

        if (null !== $customForm) {
            $this->assignation['customForm'] = $customForm;

            $form = $this->buildForm($customForm);

            $form->handleRequest();

            if ($form->isValid()) {
                try {
                    $this->editCustomForm($form->getData(), $customForm);

                    $msg = $this->getTranslator()->trans('customForm.%name%.updated', array('%name%'=>$customForm->getName()));
                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getService('logger')->info($msg);
                } catch (EntityAlreadyExistsException $e) {
                    $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                    $this->getService('logger')->warning($e->getMessage());
                }
                /*
                 * Redirect to update schema page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate(
                        'customFormsHomePage',
                        array(
                            '_token' => $this->getService('csrfProvider')->generateCsrfToken(static::SCHEMA_TOKEN_INTENTION)
                        )
                    )
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('custom-forms/edit.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
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
                    //echo "Before add node type";
                    $this->addCustomForm($form->getData(), $customForm);
                    //echo "After add node type";

                    $msg = $this->getTranslator()->trans('customForm.%name%.created', array('%name%'=>$customForm->getName()));
                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getService('logger')->info($msg);

                    /*
                     * Redirect to update schema page
                     */
                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'customFormsHomePage'
                        )
                    );

                } catch (EntityAlreadyExistsException $e) {
                    $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                    $this->getService('logger')->warning($e->getMessage());
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
                array('content-type' => 'text/html')
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
            ->find('RZ\Renzo\Core\Entities\CustomForm', (int) $customFormId);

        if (null !== $customForm) {
            $this->assignation['customForm'] = $customForm;

            $form = $this->buildDeleteForm($customForm);

            $form->handleRequest();

            if ($form->isValid() &&
                $form->getData()['customFormId'] == $customForm->getId() ) {

                $this->getService("em")->remove($customForm);

                $msg = $this->getTranslator()->trans('customForm.%name%.deleted', array('%name%'=>$customForm->getName()));
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getService('logger')->info($msg);
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
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * @param array                           $data
     * @param RZ\Renzo\Core\Entities\CustomForm $customForm
     *
     * @return boolean
     */
    private function editCustomForm($data, CustomForm $customForm)
    {
        foreach ($data as $key => $value) {
            if (isset($data['name'])) {
                throw new EntityAlreadyExistsException($this->getTranslator()->trans('customForm.%name%.cannot_rename_already_exists', array('%name%'=>$customForm->getName())), 1);
            }
            $setter = 'set'.ucwords($key);
            $customForm->$setter( $value );
        }

        $this->getService('em')->flush();

        return true;
    }

    /**
     * @param array                           $data
     * @param RZ\Renzo\Core\Entities\CustomForm $customForm
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
            ->getRepository('RZ\Renzo\Core\Entities\CustomForm')
            ->findOneBy(array('name'=>$customForm->getName()));
        if ($existing !== null) {
            throw new EntityAlreadyExistsException($this->getTranslator()->trans('customForm.%name%.already_exists', array('%name%'=>$customForm->getName())), 1);
        }

        $this->getService('em')->persist($customForm);
        $this->getService('em')->flush();

        return true;
    }

    /**
     * @param RZ\Renzo\Core\Entities\CustomForm $customForm
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildForm(CustomForm $customForm)
    {
        $defaults = array(
            'displayName' =>    $customForm->getDisplayName(),
            'description' =>    $customForm->getDescription(),
            'email' =>    $customForm->getEmail(),
            'open' =>           $customForm->isOpen(),
            'closeDate' =>      $customForm->getCloseDate(),
            'color' =>          $customForm->getColor(),
        );
        $builder = $this->getService('formFactory')
            ->createBuilder('form', $defaults)
            ->add('displayName', 'text', array(
                'label' => $this->getTranslator()->trans('customForm.displayName'),
                'constraints' => array(
                    new NotBlank()
                )))
            ->add('description', 'text', array(
                'label' => $this->getTranslator()->trans('description'),
                'required' => false
            ))
            ->add('email', 'email', array(
                'label' => $this->getTranslator()->trans('email'),
                'required' => false,
                'constraints' => array(
                    new Email()
                )
            ))
            ->add('open', 'checkbox', array(
                'label' => $this->getTranslator()->trans('customForm.open'),
                'required' => false
            ))
            ->add('closeDate', 'datetime', array(
                'label' => $this->getTranslator()->trans('customForm.closeDate'),
                'required' => false
            ))
            ->add('color', 'text', array(
                'label' => $this->getTranslator()->trans('customForm.color'),
                'required' => false,
                'attr' => array('class'=>'colorpicker-input')
            ));

        return $builder->getForm();
    }

    /**
     * @param RZ\Renzo\Core\Entities\CustomForm $customForm
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildDeleteForm(CustomForm $customForm)
    {
        $builder = $this->getService('formFactory')
            ->createBuilder('form')
            ->add('customFormId', 'hidden', array(
                'data' => $customForm->getId(),
                'constraints' => array(
                    new NotBlank()
                )
            ));

        return $builder->getForm();
    }
}
