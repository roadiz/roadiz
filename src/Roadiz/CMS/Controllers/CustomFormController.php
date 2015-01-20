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
 * @file CustomFormController.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\CMS\Controllers;

use RZ\Roadiz\CMS\Forms\CustomFormsType;
use RZ\Roadiz\Core\Bags\SettingsBag;
use RZ\Roadiz\Core\Entities\CustomForm;
use RZ\Roadiz\Core\Entities\CustomFormAnswer;
use RZ\Roadiz\Core\Entities\CustomFormFieldAttribute;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use \InlineStyle\InlineStyle;

class CustomFormController extends AppController
{
    public static $themeDir = 'Rozier';

    /**
     * @return string
     */
    public static function getResourcesFolder()
    {
        return ROADIZ_ROOT . '/src/Roadiz/CMS/Resources';
    }

    /**
     * {@inheritdoc}
     */
    public static function getRoutes()
    {
        $locator = new FileLocator(array(
        ROADIZ_ROOT . '/src/Roadiz/CMS/Resources',
        ));

        if (file_exists(ROADIZ_ROOT . '/src/Roadiz/CMS/Resources/entryPointsRoutes.yml')) {
            $loader = new YamlFileLoader($locator);

            return $loader->load('entryPointsRoutes.yml');
        }

        return null;
    }

    public function addAction(Request $request, $customFormId)
    {
        $customForm = $this->getService('em')
                           ->find("RZ\Roadiz\Core\Entities\CustomForm", $customFormId);

        if (null !== $customForm) {
            $closeDate = $customForm->getCloseDate();
            $nowDate = new \DateTime();

            if ($closeDate >= $nowDate) {
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

                        $msg = $this->getTranslator()->trans('customForm.%name%.send', array('%name%' => $customForm->getName()));
                        $request->getSession()->getFlashBag()->add('confirm', $msg);
                        $this->getService('logger')->info($msg);

                        $this->assignation['title'] = $this->getTranslator()->trans(
                            'new.answer.form.%site%',
                            array('%site%' => $customForm->getDisplayName())
                        );

                        $this->assignation['mailContact'] = SettingsBag::get('email_sender');

                        $this->sendAnswer($this->assignation, $customForm->getEmail());

                        /*
                   * Redirect to update schema page
                   */
                        $response = new RedirectResponse(
                            $this->getService('urlGenerator')->generate(
                                'customFormSendAction',
                                array("customFormId" => $customFormId)
                            )
                        );

                    } catch (EntityAlreadyExistsException $e) {
                        $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                        $this->getService('logger')->warning($e->getMessage());
                        $response = new RedirectResponse(
                            $this->getService('urlGenerator')->generate(
                                'customFormSendAction',
                                array("customFormId" => $customFormId)
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
            }
        }

        return $this->throw404();
    }

    /**
     * Send a answer form by Email.
     *
     * @param  array $assignation
     * @param  string $receiver
     *
     * @return boolean
     */
    protected function sendAnswer($assignation, $receiver)
    {
        $emailBody = $this->getTwig()->render('forms/answerForm.html.twig', $assignation);
     /*
      * inline CSS
      */
        $htmldoc = new InlineStyle($emailBody);
        $htmldoc->applyStylesheet(file_get_contents(
            ROADIZ_ROOT . "/src/Roadiz/CMS/Resources/css/transactionalStyles.css"
        ));

        if (empty($receiver)) {
            $receiver = SettingsBag::get('email_sender');
        }
     // Create the message}
        $message = \Swift_Message::newInstance()
     // Give the message a subject
        ->setSubject($this->assignation['title'])
                          // Set the From address with an associative array
                          ->setFrom(array(SettingsBag::get('email_sender')))
                          // Set the To addresses with an associative array
                          ->setTo(array($receiver))
                          // Give it a body
                          ->setBody($htmldoc->getHTML(), 'text/html');
     // Create the Transport
        $transport = \Swift_MailTransport::newInstance();
        $mailer = \Swift_Mailer::newInstance($transport);
     // Send the message
        return $mailer->send($message);
    }

    /**
     * @param array                           $data
     * @param RZ\Roadiz\Core\Entities\CustomForm $customForm
     *
     * @return boolean
     */
    private function addCustomFormAnswer($data, CustomForm $customForm)
    {
        $answer = new CustomFormAnswer();
        $answer->setIp($data["ip"]);
        $answer->setSubmittedAt(new \DateTime('NOW'));
        $answer->setCustomForm($customForm);

        $this->assignation["fields"] = array(
        array("name" => "ip", "value" => $data["ip"]),
        array("name" => "submittedAt", "value" => new \DateTime('NOW')),
        );

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

                $val = implode(',', $values);
                $fieldAttr->setValue($val);
                $this->assignation["fields"][] = array("name" => $field->getName(), "value" => $val);

            } else {
                $fieldAttr->setValue($data[$field->getName()]);
                $this->assignation["fields"][] = array("name" => $field->getName(), "value" => $data[$field->getName()]);

            }
            $this->getService('em')->persist($fieldAttr);
        }

        $this->getService('em')->flush();

        return true;
    }

    /**
     * @param RZ\Roadiz\Core\Entities\CustomForm $customForm
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildForm(Request $request, CustomForm $customForm)
    {
        $defaults = $request->query->all();
        $form = $this->getService('formFactory')
                     ->create(new CustomFormsType($customForm), $defaults);

        return $form;
    }
}
