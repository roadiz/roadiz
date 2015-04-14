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

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use RZ\Roadiz\CMS\Forms\CustomFormsType;
use RZ\Roadiz\Core\Bags\SettingsBag;
use RZ\Roadiz\Core\Entities\CustomForm;
use RZ\Roadiz\Core\Entities\CustomFormAnswer;
use RZ\Roadiz\Core\Entities\CustomFormFieldAttribute;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;
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
        $locator = new FileLocator([
            ROADIZ_ROOT . '/src/Roadiz/CMS/Resources',
        ]);

        if (file_exists(ROADIZ_ROOT . '/src/Roadiz/CMS/Resources/entryPointsRoutes.yml')) {
            $loader = new YamlFileLoader($locator);

            return $loader->load('entryPointsRoutes.yml');
        }

        return null;
    }

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int $customFormId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request, $customFormId)
    {
        $customForm = $this->getService('em')
                           ->find("RZ\Roadiz\Core\Entities\CustomForm", $customFormId);

        if (null !== $customForm &&
            $customForm->isFormStillOpen()) {
            $mixed = static::prepareAndHandleCustomFormAssignation(
                $request,
                $customForm,
                $this->getService('formFactory'),
                $this->getService('em'),
                $this->getService('twig.environment'),
                $this->getService('mailer'),
                $this->getService('translator'),
                new RedirectResponse(
                    $this->generateUrl(
                        'customFormSentAction',
                        ["customFormId" => $customFormId]
                    )
                ),
                $this->getService('logger')
            );

            if ($mixed instanceof RedirectResponse) {
                $mixed->prepare($request);
                return $mixed->send();
            } else {
                $this->assignation = array_merge($this->assignation, $mixed);
                $this->assignation['grunt'] = include ROADIZ_ROOT . '/themes/Rozier/static/public/config/assets.config.php';

                return $this->render('forms/customForm.html.twig', $this->assignation);
            }
        }

        return $this->throw404();
    }

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int  $customFormId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function sentAction(Request $request, $customFormId)
    {
        $customForm = $this->getService('em')
                           ->find("RZ\Roadiz\Core\Entities\CustomForm", $customFormId);

        if (null !== $customForm) {
            $this->assignation['customForm'] = $customForm;
            $this->assignation['grunt'] = include ROADIZ_ROOT . '/themes/Rozier/static/public/config/assets.config.php';

            return $this->render('forms/customFormSent.html.twig', $this->assignation);
        }

        return $this->throw404();
    }

    /**
     * Send an answer form by Email.
     *
     * @param  array             $assignation
     * @param  string            $receiver
     * @param  \Twig_Environment $twigEnv
     * @param  \Swift_Mailer     $mailer
     *
     * @return boolean
     */
    public static function sendAnswer(
        $assignation,
        $receiver,
        \Twig_Environment $twigEnv,
        \Swift_Mailer $mailer
    ) {
        $emailBody = $twigEnv->render('forms/answerForm.html.twig', $assignation);

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
        $message = \Swift_Message::newInstance();
        // Give the message a subject
        $message->setSubject($assignation['title']);
        // Set the From address with an associative array
        $message->setFrom([SettingsBag::get('email_sender')]);
        // Set the To addresses with an associative array
        $message->setTo([$receiver]);
        // Give it a body
        $message->setBody($htmldoc->getHTML(), 'text/html');

        // Send the message
        return $mailer->send($message);
    }

    /**
     * Add a custom form answer into database.
     *
     * @param array $data Data array from POST form
     * @param RZ\Roadiz\Core\Entities\CustomForm $customForm
     * @param Doctrine\ORM\EntityManager $em
     *
     * @return array $fieldsData
     */
    public static function addCustomFormAnswer(array $data, CustomForm $customForm, EntityManager $em)
    {
        $now = new \DateTime('NOW');
        $answer = new CustomFormAnswer();
        $answer->setIp($data["ip"]);
        $answer->setSubmittedAt($now);
        $answer->setCustomForm($customForm);

        $fieldsData = [
            ["name" => "ip.address", "value" => $data["ip"]],
            ["name" => "submittedAt", "value" => $now],
        ];

        $em->persist($answer);

        foreach ($customForm->getFields() as $field) {
            $fieldAttr = new CustomFormFieldAttribute();
            $fieldAttr->setCustomFormAnswer($answer);
            $fieldAttr->setCustomFormField($field);

            if ($data[$field->getName()] instanceof \DateTime) {
                $strDate = $data[$field->getName()]->format('Y-m-d H:i:s');

                $fieldAttr->setValue($strDate);
                $fieldsData[] = ["name" => $field->getLabel(), "value" => $strDate];

            } else if (is_array($data[$field->getName()])) {
                $values = $data[$field->getName()];

                $values = array_map('trim', $values);
                $values = array_map('strip_tags', $values);

                $displayValues = implode(', ', $values);
                $fieldAttr->setValue($displayValues);
                $fieldsData[] = ["name" => $field->getLabel(), "value" => $displayValues];

            } else {
                $fieldAttr->setValue(strip_tags($data[$field->getName()]));
                $fieldsData[] = ["name" => $field->getLabel(), "value" => $data[$field->getName()]];
            }
            $em->persist($fieldAttr);
        }

        $em->flush();

        return $fieldsData;
    }

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param RZ\Roadiz\Core\Entities\CustomForm $customForm
     * @param Symfony\Component\Form\FormFactoryInterface $formFactory
     * @param boolean $forceExpanded
     *
     * @return \Symfony\Component\Form\Form
     */
    public static function buildForm(
        Request $request,
        CustomForm $customForm,
        FormFactoryInterface $formFactory,
        $forceExpanded = false
    ) {
        $defaults = $request->query->all();
        return $formFactory->create(new CustomFormsType($customForm, $forceExpanded), $defaults);
    }

    /**
     * Prepare and handle a CustomForm Form then send a confirm email.
     *
     * * This method will return an assignation **array** if form is not validated.
     *     * customForm
     *     * fields
     *     * form
     * * If form is validated, **RedirectResponse** will be returned.
     *
     * @param Symfony\Component\HttpFoundation\Request          $request
     * @param RZ\Roadiz\Core\Entities\CustomForm                $customFormsEntity
     * @param Symfony\Component\Form\FormFactoryInterface       $formFactory
     * @param Doctrine\ORM\EntityManager                        $em
     * @param \Twig_Environment                                 $twigEnv
     * @param \Swift_Mailer                                     $mailer
     * @param Symfony\Component\Translation\Translator          $translator
     * @param Symfony\Component\HttpFoundation\RedirectResponse $redirection
     * @param Psr\Log\LoggerInterface|null                      $logger
     * @param boolean                                           $forceExpanded
     * @param string|null                                       $emailSender
     *
     * @return array|Symfony\Component\HttpFoundation\RedirectResponse
     */
    public static function prepareAndHandleCustomFormAssignation(
        Request $request,
        CustomForm $customFormsEntity,
        FormFactoryInterface $formFactory,
        EntityManager $em,
        \Twig_Environment $twigEnv,
        \Swift_Mailer $mailer,
        Translator $translator,
        RedirectResponse $redirection,
        LoggerInterface $logger = null,
        $forceExpanded = false,
        $emailSender = null
    ) {
        $assignation = [];
        $assignation['customForm'] = $customFormsEntity;
        $assignation['fields'] = $customFormsEntity->getFields();

        $form = static::buildForm(
            $request,
            $customFormsEntity,
            $formFactory,
            $forceExpanded
        );

        $form->handleRequest();

        if ($form->isValid()) {
            try {
                $data = $form->getData();
                $data["ip"] = $request->getClientIp();

                /*
                 * add custom form answer
                 */
                $assignation["emailFields"] = static::addCustomFormAnswer(
                    $data,
                    $customFormsEntity,
                    $em
                );

                $msg = $translator->trans(
                    'customForm.%name%.send',
                    ['%name%' => $customFormsEntity->getDisplayName()]
                );

                $request->getSession()->getFlashBag()->add('confirm', $msg);

                if (null !== $logger) {
                    $logger->info($msg);
                }

                $assignation['title'] = $translator->trans(
                    'new.answer.form.%site%',
                    ['%site%' => $customFormsEntity->getDisplayName()]
                );

                if (null !== $emailSender &&
                    false !== filter_var($emailSender, FILTER_VALIDATE_EMAIL)) {
                    $assignation['mailContact'] = $emailSender;
                } else {
                    $assignation['mailContact'] = SettingsBag::get('email_sender');
                }

                /*
                 * Send answer notification
                 */
                static::sendAnswer(
                    [
                        'fields' => $assignation["emailFields"],
                        'customForm' => $customFormsEntity,
                        'title' => $translator->trans(
                            'new.answer.form.%site%',
                            ['%site%' => $customFormsEntity->getDisplayName()]
                        ),
                    ],
                    $customFormsEntity->getEmail(),
                    $twigEnv,
                    $mailer
                );

                return $redirection;
            } catch (EntityAlreadyExistsException $e) {
                $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                if (null !== $logger) {
                    $logger->warning($e->getMessage());
                }

                return $redirection;
            }
        }

        $assignation['form'] = $form->createView();

        return $assignation;
    }
}
