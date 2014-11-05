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

namespace RZ\Renzo\CMS\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use RZ\Renzo\Core\Entities\CustomForm;
use RZ\Renzo\Core\Entities\CustomFormField;
use RZ\Renzo\Core\Entities\CustomFormFieldAttribute;
use RZ\Renzo\Core\Entities\CustomFormAnswer;


class CustomFormController extends AppController
{
    /**
     * Initialize controller with NO twig environment.
     */
    public function __init()
    {
        $this->getTwigLoader()
             ->initializeTwig()
             ->initializeTranslator()
             ->prepareBaseAssignation();
    }

    /**
     * @return string
     */
    public static function getResourcesFolder()
    {
        return RENZO_ROOT.'/src/Renzo/CMS/Resources';
    }

    /**
     * {@inheritdoc}
     */
    public static function getRoutes()
    {
        $locator = new FileLocator(array(
            RENZO_ROOT.'/src/Renzo/CMS/Resources'
        ));

        if (file_exists(RENZO_ROOT.'/src/Renzo/CMS/Resources/entryPointsRoutes.yml')) {
            $loader = new YamlFileLoader($locator);

            return $loader->load('entryPointsRoutes.yml');
        }

        return null;
    }

    public function addAction(Request $request, $customFormId)
    {
        $customForm = $this->getService('em')->find("RZ\Renzo\Core\Entities\CustomForm", $customFormId);

        if (null !== $customForm) {
            $this->assignation['customForm'] = $customForm;

            /*
             * form
             */
            $form = $this->buildForm($customForm);
            $form->handleRequest();
            if ($form->isValid()) {
                try {

                    $data = $form->getData();
                    $data["ip"] = $request->getClientIp();
                    $this->addCustomFormAnswer($data, $customForm);

                    $msg = $this->getTranslator()->trans('customForm.%name%.send', array('%name%'=>$customForm->getName()));
                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getService('logger')->info($msg);

                    /*
                     * Redirect to update schema page
                     */
                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'customFormSendAction', array("customFormId" => $customFormId)
                        )
                    );

                } catch (EntityAlreadyExistsException $e) {
                    $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                    $this->getService('logger')->warning($e->getMessage());
                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'customFormSendAction', array("customFormId" => $customFormId)
                        )
                    );
                }
                $response->prepare($request);

                return $response->send();
                return;
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('forms/customForm.html.twig', $this->assignation),
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
    private function addCustomFormAnswer($data, CustomForm $customForm)
    {
        // $existing = $this->getService('em')
        //     ->getRepository('RZ\Renzo\Core\Entities\CustomFormAnswer')
        //     ->findOneBy(array('ip'=>$data->getClientIp()));

        // if ($existing !== null) {
        //     throw new EntityAlreadyExistsException($this->getTranslator()->trans('customForm.%name%.already_exists', array('%name%'=>$customForm->getName())), 1);
        // }

        $answer = new CustomFormAnswer();
        $answer->setIp($data["ip"]);
        $answer->setSummittedTime(new \DateTime('NOW'));
        $answer->setCustomForm($customForm);

        $this->getService('em')->persist($answer);

        foreach ($customForm->getFields() as $field) {
            $fieldAttr = new CustomFormFieldAttribute();
            $fieldAttr->setCustomFormAnswer($answer);
            $fieldAttr->setCustomFormField($field);
            $fieldAttr->setValue($data[$field->getName()]);

            $this->getService('em')->persist($fieldAttr);
        }

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
        $defaults = array();

        $fields = $customForm->getFields();
        // foreach ($fields as $field) {
        //     $defaults[] = array($field->getName()=>null);
        // }

        $builder = $this->getService('formFactory')
            ->createBuilder('form', $defaults);
            foreach ($fields as $field) {
                if ($field->isRequire()) {
                    $builder->add($field->getName(), CustomFormField::$typeToForm[$field->getType()], array(
                                  'label' => $field->getLabel(),
                                  'constraints' => array(
                                    new NotBlank()
                                 )));
                } else {
                    $builder->add($field->getName(), CustomFormField::$typeToForm[$field->getType()], array(
                                  'label' => $field->getLabel(),
                                  'required' => false
                                  ));
                }
            }
            // ->add('name', 'text', array(
            //     'label' => $this->getTranslator()->trans('name'),
            //     'constraints' => array(
            //         new NotBlank()
            //     )))
            // ->add('displayName', 'text', array(
            //     'label' => $this->getTranslator()->trans('customForm.displayName'),
            //     'constraints' => array(
            //         new NotBlank()
            //     )))
            // ->add('description', 'text', array(
            //     'label' => $this->getTranslator()->trans('description'),
            //     'required' => false
            // ))
            // ->add('open', 'checkbox', array(
            //     'label' => $this->getTranslator()->trans('open'),
            //     'required' => false
            // ))
            // ->add('closeDate', 'datetime', array(
            //     'label' => $this->getTranslator()->trans('closeDate'),
            //     'required' => false
            // ))
            // ->add('color', 'text', array(
            //     'label' => $this->getTranslator()->trans('customForm.color'),
            //     'required' => false,
            //     'attr' => array('class'=>'colorpicker-input')
            // ));

        return $builder->getForm();
    }
}