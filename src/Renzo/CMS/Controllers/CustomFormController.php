<?php
/**
 * Copyright © 2014, REZO ZERO
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
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file CustomFormController.php
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
use RZ\Renzo\CMS\Forms\CustomFormsType;


class CustomFormController extends AppController
{
    public static $themeDir = 'Rozier';

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
            $closeDate = $customForm->getCloseDate();

            $nowDate = new \DateTime();
        }

        if (null !== $customForm && $closeDate >= $nowDate) {
            $this->assignation['customForm'] = $customForm;
            $this->assignation['fields'] = $customForm->getFields();

            /*
             * form
             */
            $form = $this->buildForm($request, $customForm);
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
        $answer = new CustomFormAnswer();
        $answer->setIp($data["ip"]);
        $answer->setSubmittedAt(new \DateTime('NOW'));
        $answer->setCustomForm($customForm);

        $this->getService('em')->persist($answer);

        foreach ($customForm->getFields() as $field) {
            $fieldAttr = new CustomFormFieldAttribute();
            $fieldAttr->setCustomFormAnswer($answer);
            $fieldAttr->setCustomFormField($field);

            if (is_array($data[$field->getName()])) {

                $values = array();

                foreach ($data[$field->getName()] as $value) {
                    $choices = explode(',', $field->getDefaultValues());
                    $values[] = $choices[$value];
                }

                $fieldAttr->setValue(implode(',', $values));

            } elseif (CustomFormField::$typeToForm[$field->getType()] == "enumeration") {

                $choices = explode(',', $field->getDefaultValues());

                $fieldAttr->setValue($data[$field->getName()]);

            } else {
                $fieldAttr->setValue($data[$field->getName()]);
            }
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
    private function buildForm(Request $request, CustomForm $customForm)
    {
        $fields = $customForm->getFields();

        $defaults = $request->query->all();
        $form = $this->getService('formFactory')->create(new CustomFormsType($customForm), $defaults);

        return $form;
    }
}