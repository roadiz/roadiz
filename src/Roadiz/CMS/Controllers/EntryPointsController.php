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
 * @file EntryPointsController.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\CMS\Controllers;

use RZ\Roadiz\Core\Bags\SettingsBag;
use RZ\Roadiz\Core\Exceptions\BadFormRequestException;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\EmailManager;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Security\Csrf\CsrfToken;

/**
 * Defines entry points for Roadiz.
 */
class EntryPointsController extends CmsController
{
    const CONTACT_FORM_TOKEN_INTENTION = 'contact_form';

    protected static $mandatoryContactFields = [
        'email',
        'message',
    ];

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string                                   $method
     *
     * @return boolean | array  Return true if request is valid, else return error array
     */
    protected function validateRequest(Request $request, $method = 'POST')
    {
        if ($request->getMethod() != $method ||
            !is_array($request->get('form'))) {
            return [
                'statusCode' => Response::HTTP_FORBIDDEN,
                'status' => 'danger',
                'responseText' => 'Wrong request',
            ];
        }
        $token = new CsrfToken(static::CONTACT_FORM_TOKEN_INTENTION, $request->get('form')['_token']);
        if (!$this->get('csrfTokenManager')->isTokenValid($token)) {
            return [
                'statusCode' => Response::HTTP_FORBIDDEN,
                'status' => 'danger',
                'responseText' => 'Bad token',
            ];
        }

        return true;
    }

    /**
     * Handles contact forms requests.
     *
     * File upload are allowed if file size is less than 5MB and mimeType
     * is PDF or image.
     *
     * * application/pdf
     * * application/x-pdf
     * * image/jpeg
     * * image/png
     * * image/gif
     *
     * @param  \Symfony\Component\HttpFoundation\Request $request
     * @deprecated Use ContactFormManager instead
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function contactFormAction(Request $request)
    {
        if (true !== $validation = $this->validateRequest($request)) {
            return new JsonResponse(
                $validation,
                Response::HTTP_FORBIDDEN
            );
        }

        try {
            $this->validateMandatoryFields($request);
            $this->validateEmail($request);
            $uploadedFiles = $this->getUploadedFiles($request);
            $subject = $this->getSubject($request);
            $receiver = $this->getReceiver($request);

            $assignation = [
                'mailContact' => SettingsBag::get('email_sender'),
                'title' => $this->getTranslator()->trans(
                    'new.contact.form.%site%',
                    ['%site%' => SettingsBag::get('site_name')]
                ),
                'email' => $request->get('form')['email'],
                'fields' => $this->prepareFieldsAssignation($request, $uploadedFiles),
            ];

            /*
             * Send contact form email
             */
            $this->sendContactForm($assignation, $receiver, $subject, $uploadedFiles);

            $responseArray = [
                'statusCode' => Response::HTTP_OK,
                'status' => 'success',
                'field_error' => null,
                'message' => $this->getTranslator()->trans(
                    'form.successfully.sent'
                ),
            ];
            $request->getSession()->getFlashBag()->add('confirm', $responseArray['message']);
            $this->get('logger')->info($responseArray['message']);

            if (empty($request->get('form')['_redirect'])) {
                return new JsonResponse($responseArray);
            }
        } catch (BadFormRequestException $e) {
            $request->getSession()->getFlashBag()->add('error', $e->getMessage());
            $this->get('logger')->warning($e->getMessage());

            if (empty($request->get('form')['_redirect'])) {
                return new JsonResponse([
                    'statusCode' => $e->getCode(),
                    'status' => $e->getStatusText(),
                    'field_error' => $e->getFieldErrored(),
                    'message' => $e->getMessage(),
                ]);
            }
        } catch (\Exception $e) {
            $request->getSession()->getFlashBag()->add('error', $e->getMessage());
            $this->get('logger')->warning($e->getMessage());

            if (empty($request->get('form')['_redirect'])) {
                return new JsonResponse([
                    'statusCode' => $e->getCode(),
                    'message' => $e->getMessage(),
                ]);
            }
        }

        /*
         * If no AJAX and a redirect URL is present,
         * just redirect.
         */
        return $this->redirect($request->get('form')['_redirect']);
    }

    /**
     * @param Request $request
     * @throws BadFormRequestException
     */
    protected function validateMandatoryFields(Request $request)
    {
        foreach (static::$mandatoryContactFields as $mandatoryField) {
            if (empty($request->get('form')[$mandatoryField])) {
                throw new BadFormRequestException(
                    $this->getTranslator()->trans(
                        '%field%.is.mandatory',
                        ['%field%' => ucwords($mandatoryField)]
                    ),
                    Response::HTTP_FORBIDDEN,
                    'danger',
                    $mandatoryField
                );
            }
        }
    }

    /**
     * @param Request $request
     * @throws BadFormRequestException
     */
    protected function validateEmail(Request $request)
    {
        if (false === filter_var($request->get('form')['email'], FILTER_VALIDATE_EMAIL)) {
            throw new BadFormRequestException(
                $this->getTranslator()->trans(
                    'email.not.valid'
                ),
                Response::HTTP_FORBIDDEN,
                'danger',
                'email'
            );
        }
    }
    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param array $uploadedFiles
     *
     * @return array
     */
    protected function prepareFieldsAssignation(Request $request, array $uploadedFiles = [])
    {
        $fields = [];
        /*
         * text values
         */
        foreach ($request->get('form') as $key => $value) {
            if ($key[0] == '_') {
                continue;
            } elseif (!empty($value)) {
                $fields[] = [
                    'name' => strip_tags($key),
                    'value' => (strip_tags($value)),
                ];
            }
        }
        /*
         * Files values
         */
        foreach ($uploadedFiles as $key => $uploadedFile) {
            $fields[] = [
                'name' => strip_tags($key),
                'value' => (strip_tags($uploadedFile->getClientOriginalName()) .
                    ' [' . $uploadedFile->guessExtension() . ']'),
            ];
        }
        /*
         *  Date
         */
        $fields[] = [
            'name' => $this->getTranslator()->trans('date'),
            'value' => (new \DateTime())->format('Y-m-d H:i:s'),
        ];
        /*
         *  IP
         */
        $fields[] = [
            'name' => $this->getTranslator()->trans('ip.address'),
            'value' => $request->getClientIp(),
        ];

        return $fields;
    }
    /**
     * @param  Request $request
     * @return string
     */
    protected function getSubject(Request $request)
    {
        /*
         * Custom subject
         */
        if (!empty($request->get('form')['_emailSubject'])) {
            return StringHandler::decodeWithSecret(
                $request->get('form')['_emailSubject'],
                $this->get('config')['security']['secret']
            );
        } else {
            return null;
        }
    }
    /**
     * @param  Request $request
     * @return string
     */
    protected function getReceiver(Request $request)
    {
        /*
         * Custom receiver
         */
        if (!empty($request->get('form')['_emailReceiver'])) {
            $email = StringHandler::decodeWithSecret(
                $request->get('form')['_emailReceiver'],
                $this->get('config')['security']['secret']
            );
            if (false !== filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $email;
            }
        }

        return SettingsBag::get('email_sender');
    }

    /**
     * @param  Request $request
     * @return array
     * @throws BadFormRequestException
     */
    protected function getUploadedFiles(Request $request)
    {
        $allowedMimeTypes = [
            'application/pdf',
            'application/x-pdf',
            'image/jpeg',
            'image/png',
            'image/gif',
        ];
        $maxFileSize = 1024 * 1024 * 5; // 5MB
        $uploadedFiles = [];
        /*
         * Files values
         */
        foreach ($request->files as $files) {
            /**
             * @var string $name
             * @var UploadedFile $uploadedFile
             */
            foreach ($files as $name => $uploadedFile) {
                if (null !== $uploadedFile) {
                    if (!$uploadedFile->isValid() ||
                        !in_array($uploadedFile->getMimeType(), $allowedMimeTypes) ||
                        $uploadedFile->getClientSize() > $maxFileSize) {
                        throw new BadFormRequestException(
                            $this->getTranslator()->trans(
                                'file.not.accepted'
                            ),
                            Response::HTTP_FORBIDDEN,
                            'danger',
                            $name
                        );
                    } else {
                        $uploadedFiles[$name] = $uploadedFile;
                    }
                }
            }
        }

        return $uploadedFiles;
    }

    /**
     * Generate a form-builder for contact forms.
     *
     * For example in your contact page controller :
     *
     * <pre>
     * use RZ\Roadiz\CMS\Controllers\EntryPointsController;
     *
     * ...
     *
     * $formBuilder = EntryPointsController::getContactFormBuilder(
     *     $request,
     *     true
     * );
     * $formBuilder->add('email', 'email', array(
     *                 'label'=>$this->getTranslator()->trans('your.email')
     *             ))
     *             ->add('name', 'text', array(
     *                 'label'=>$this->getTranslator()->trans('your.name')
     *             ))
     *             ->add('message', 'textarea', array(
     *                 'label'=>$this->getTranslator()->trans('your.message')
     *             ))
     *             ->add('callMeBack', 'checkbox', array(
     *                 'label'=>$this->getTranslator()->trans('call.me.back'),
     *                 'required' => false
     *             ))
     *             ->add('send', 'submit', array(
     *                 'label'=>$this->getTranslator()->trans('send.contact.form')
     *             ));
     * $form = $formBuilder->getForm();
     * $this->assignation['contactForm'] = $form->createView();
     *
     * </pre>
     *
     * Add session messages to your assignations
     *
     * <pre>
     * // Get session messages
     * $this->assignation['session']['messages'] = $this->get('session')->getFlashBag()->all();
     * </pre>
     *
     * Then in your contact page Twig template
     *
     * <pre>
     * {#
     *  # Display contact errors
     *  #}
     * {% if session.messages|length %}
     *     {% for type, msgs in session.messages %}
     *         {% for msg in msgs %}
     *             <div data-uk-alert class="uk-alert
     *                                       uk-alert-{% if type == "confirm" %}success
     *                                       {% elseif type == "warning" %}warning{% else %}danger{% endif %}">
     *                 <a href="" class="uk-alert-close uk-close"></a>
     *                 <p>{{ msg }}</p>
     *             </div>
     *         {% endfor %}
     *     {% endfor %}
     * {% endif %}
     * {#
     *  # Display contact form
     *  #}
     * {% form_theme contactForm 'forms.html.twig' %}
     * {{ form(contactForm) }}
     * </pre>
     *
     * @param \Symfony\Component\HttpFoundation\Request $request             Contact page request
     * @param boolean                                   $redirect            Redirect to contact page after sending?
     * @param string                                    $customRedirectUrl   Redirect to a custom url
     * @param string                                    $customEmailReceiver Send contact form to a custom email (or emails)
     * @param string                                    $customEmailSubject  Customize email subject
     *
     * @deprecated Use ContactFormManager instead
     *
     * @return \Symfony\Component\Form\FormBuilder
     */
    public static function getContactFormBuilder(
        Request $request,
        $redirect = true,
        $customRedirectUrl = null,
        $customEmailReceiver = null,
        $customEmailSubject = null
    ) {
        $action = Kernel::getService('urlGenerator')
            ->generate('contactFormLocaleAction', [
                '_locale' => $request->getLocale(),
            ]);

        $builder = Kernel::getService('formFactory')
            ->createBuilder('form', null, [
                'intention' => static::CONTACT_FORM_TOKEN_INTENTION,
                'attr' => [
                    'id' => 'contactForm',
                ],
            ])
            ->setMethod('POST')
            ->setAction($action)
            ->add('_action', 'hidden', [
                'data' => 'contact',
            ]);

        if (true === $redirect) {
            if (null !== $customRedirectUrl) {
                $builder->add('_redirect', 'hidden', [
                    'data' => strip_tags($customRedirectUrl),
                ]);
            } else {
                $builder->add('_redirect', 'hidden', [
                    'data' => strip_tags($request->getUri()),
                ]);
            }
        }

        if (null !== $customEmailReceiver) {
            $builder->add('_emailReceiver', 'hidden', [
                'data' => StringHandler::encodeWithSecret($customEmailReceiver, Kernel::getService('config')['security']['secret']),
            ]);
        }

        if (null !== $customEmailSubject) {
            $builder->add('_emailSubject', 'hidden', [
                'data' => StringHandler::encodeWithSecret($customEmailSubject, Kernel::getService('config')['security']['secret']),
            ]);
        }

        return $builder;
    }

    /**
     * Send a contact form by Email.
     *
     * @param array $assignation
     * @param string $receiver
     * @param string|null $subject
     * @param array $files
     * @deprecated Use ContactFormManager instead
     *
     * @return boolean
     */
    protected function sendContactForm($assignation, $receiver, $subject = null, $files = null)
    {
        $emailManager = new EmailManager(
            $this->get('request'),
            $this->get('translator'),
            $this->get('twig.environment'),
            $this->get('mailer')
        );
        $emailManager->setAssignation($assignation);
        $emailManager->setEmailTemplate('forms/contactForm.html.twig');
        $emailManager->setEmailPlainTextTemplate('forms/contactForm.txt.twig');

        if (null !== $subject) {
            $emailManager->setSubject($subject);
        } else {
            $emailManager->setSubject($this->getTranslator()->trans(
                'new.contact.form.%site%',
                ['%site%' => SettingsBag::get('site_name')]
            ));
        }

        $emailManager->setEmailTitle($assignation['title']);

        if (empty($receiver)) {
            $emailManager->setReceiver(SettingsBag::get('email_sender'));
        } else {
            $emailManager->setReceiver($receiver);
        }

        $emailManager->setSender($assignation['email']);

        $message = $emailManager->createMessage();
        /*
         * Attach files
         */
        foreach ($files as $uploadedFile) {
            $attachment = \Swift_Attachment::fromPath($uploadedFile->getRealPath())
                ->setFilename($uploadedFile->getClientOriginalName());
            $message->attach($attachment);
        }

        // Send the message
        return $emailManager->send();
    }
}
