<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
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
 * @file ContactFormManager.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Utils;

use RZ\Roadiz\Utils\UrlGenerators\DocumentUrlGenerator;
use RZ\Roadiz\CMS\Forms\Constraints\Recaptcha;
use RZ\Roadiz\CMS\Forms\RecaptchaType;
use RZ\Roadiz\Core\Bags\Settings;
use RZ\Roadiz\Core\Exceptions\BadFormRequestException;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class ContactFormManager
 * @package RZ\Roadiz\Utils
 */
class ContactFormManager extends EmailManager
{
    /** @var array|null  */
    protected $uploadedFiles = null;

    /**
     * @var string
     */
    protected $redirectUrl = null;
    /**
     * @var FormBuilder
     */
    protected $formBuilder = null;

    /**
     * @var Form
     */
    protected $form = null;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var string
     */
    protected $method = Request::METHOD_POST;

    /**
     * @var array
     */
    protected $allowedMimeTypes = [
        'application/pdf',
        'application/x-pdf',
        'image/jpeg',
        'image/png',
        'image/gif',
    ];

    /**
     * @var int
     */
    protected $maxFileSize = 5242880;
    /**
     * @var FormFactoryInterface
     */
    protected $formFactory; // 5MB

    /**
     * ContactFormManager constructor.
     *
     * DO NOT DIRECTLY USE THIS CONSTRUCTOR
     * USE 'contactFormManager' Factory Service
     *
     * @param Request $request
     * @param FormFactoryInterface $formFactory
     * @param TranslatorInterface $translator
     * @param \Twig_Environment $templating
     * @param \Swift_Mailer $mailer
     * @param Settings|null $settingsBag
     * @param DocumentUrlGenerator $documentUrlGenerator
     */
    public function __construct(
        Request $request,
        FormFactoryInterface $formFactory,
        TranslatorInterface $translator,
        \Twig_Environment $templating,
        \Swift_Mailer $mailer,
        Settings $settingsBag,
        DocumentUrlGenerator $documentUrlGenerator
    ) {
        parent::__construct($request, $translator, $templating, $mailer, $settingsBag, $documentUrlGenerator);

        $this->formFactory = $formFactory;
        $this->options = [
            'attr' => [
                'id' => 'contactForm',
            ],
        ];

        $this->successMessage = 'form.successfully.sent';
        $this->failMessage = 'form.has.errors';
        $this->emailTemplate = 'forms/contactForm.html.twig';
        $this->emailPlainTextTemplate = 'forms/contactForm.txt.twig';

        $this->setSubject($this->translator->trans(
            'new.contact.form.%site%',
            ['%site%' => $this->settingsBag->get('site_name')]
        ));

        $this->setEmailTitle($this->translator->trans(
            'new.contact.form.%site%',
            ['%site%' => $this->settingsBag->get('site_name')]
        ));
    }

    /**
     * @return $this
     */
    public function disableCsrfProtection()
    {
        $this->options['csrf_protection'] = false;

        return $this;
    }

    /**
     * @return FormBuilderInterface
     */
    public function getFormBuilder()
    {
        if (null === $this->formBuilder) {
            $this->formBuilder = $this->formFactory
                ->createBuilder(FormType::class, null, $this->options)
                ->setMethod($this->method);
        }
        return $this->formBuilder;
    }

    /**
     * @return Form
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * Adds a email, name and message fields with their constraints.
     *
     * @return ContactFormManager $this
     */
    public function withDefaultFields()
    {
        $this->getFormBuilder()->add('email', 'email', [
            'label' => 'your.email',
            'constraints' => [
                new NotBlank(),
                new Email([
                    'message' => 'email.not.valid',
                ]),
            ],
        ])
            ->add('name', 'text', [
                'label' => 'your.name',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('message', 'textarea', [
                'label' => 'your.message',
                'constraints' => [
                    new NotBlank(),
                ],
            ]);

        return $this;
    }

    /**
     * Add a Google recaptcha to your contact form.
     *
     * Make sure you’ve added recaptcha form template and filled
     * recaptcha_public_key and recaptcha_private_key settings.
     *
     *   <script src='https://www.google.com/recaptcha/api.js'></script>
     *
     *   {% block recaptcha_widget -%}
     *       <div class="g-recaptcha" data-sitekey="{{ configs.publicKey }}"></div>
     *   {%- endblock recaptcha_widget %}
     *
     *
     * @return ContactFormManager $this
     */
    public function withGoogleRecaptcha()
    {
        $publicKey = $this->settingsBag->get('recaptcha_public_key');
        $privateKey = $this->settingsBag->get('recaptcha_private_key');
        $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';

        if (!empty($publicKey) &&
            !empty($privateKey)) {
            $this->getFormBuilder()->add('recaptcha', new RecaptchaType(), [
                'label' => false,
                'configs' => [
                    'publicKey' => $publicKey,
                ],
                'constraints' => [
                    new Recaptcha($this->request, [
                        'privateKey' => $privateKey,
                        'verifyUrl' => $verifyUrl,
                    ]),
                ],
            ]);
        }

        return $this;
    }

    /**
     * Handle custom form validation and send it as an email.
     *
     * @return Response|null
     */
    public function handle()
    {
        $this->form = $this->getFormBuilder()->getForm();
        $this->form->handleRequest($this->request);
        $returnJson = $this->request->isXmlHttpRequest() ||
            ($this->request->attributes->has('_format') && $this->request->attributes->get('_format') == 'json');

        if ($this->form->isSubmitted()) {
            if ($this->form->isValid()) {
                $this->handleFiles();
                $this->handleFormData($this->form);

                if ($this->send() > 0) {
                    if ($returnJson) {
                        $responseArray = [
                            'statusCode' => Response::HTTP_OK,
                            'status' => 'success',
                            'message' => $this->translator->trans($this->successMessage),
                        ];
                        return new JsonResponse($responseArray);
                    } else {
                        $this->request
                             ->getSession()
                             ->getFlashBag()
                             ->add('confirm', $this->translator->trans($this->successMessage));
                        $this->redirectUrl = $this->redirectUrl !== null ? $this->redirectUrl : $this->request->getUri();
                        return new RedirectResponse($this->redirectUrl);
                    }
                }
            } elseif ($returnJson) {
                /*
                 * If form has errors during AJAX
                 * request we sent them.
                 */
                $errorPerForm = [];
                foreach ($this->form as $child) {
                    if (!$child->isValid()) {
                        foreach ($child->getErrors() as $error) {
                            $errorPerForm[$child->getName()][] = $this->translator->trans($error->getMessage());
                        }
                    }
                }
                $responseArray = [
                    'statusCode' => Response::HTTP_BAD_REQUEST,
                    'status' => 'danger',
                    'message' => $this->translator->trans($this->failMessage),
                    'errors' => (string) $this->form->getErrors(),
                    'errorsPerForm' => $errorPerForm,
                ];
                return new JsonResponse($responseArray);
            }
        }

        return null;
    }

    /**
     * @throws BadFormRequestException
     */
    protected function handleFiles()
    {
        $this->uploadedFiles = [];

        /*
         * Files values
         */
        foreach ($this->request->files as $files) {
            /**
             * @var string $name
             * @var UploadedFile $uploadedFile
             */
            foreach ($files as $name => $uploadedFile) {
                if (null !== $uploadedFile) {
                    if (!$uploadedFile->isValid() ||
                        !in_array($uploadedFile->getMimeType(), $this->allowedMimeTypes) ||
                        $uploadedFile->getClientSize() > $this->maxFileSize) {
                        throw new BadFormRequestException(
                            $this->translator->trans('file.not.accepted'),
                            Response::HTTP_FORBIDDEN,
                            'danger',
                            $name
                        );
                    } else {
                        $this->uploadedFiles[$name] = $uploadedFile;
                    }
                }
            }
        }
    }

    /**
     * @param Form $form
     */
    protected function handleFormData(Form $form)
    {
        $formData = $form->getData();
        $fields = $this->flattenFormData($formData, []);

        /*
         * Sender email
         */
        if (!empty($formData['email'])) {
            $this->setSender($formData['email']);
        }

        /**
         * @var string $key
         * @var UploadedFile $uploadedFile
         */
        foreach ($this->uploadedFiles as $key => $uploadedFile) {
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
            'name' => $this->translator->trans('date'),
            'value' => (new \DateTime())->format('Y-m-d H:i:s'),
        ];
        /*
         *  IP
         */
        $fields[] = [
            'name' => $this->translator->trans('ip.address'),
            'value' => $this->request->getClientIp(),
        ];

        $this->assignation = [
            'mailContact' => $this->settingsBag->get('email_sender'),
            'title' => $this->getEmailTitle(),
            'email' => $this->getSender(),
            'fields' => $fields,
        ];
    }

    /**
     * @param array $formData
     * @param array $fields
     * @return array
     */
    protected function flattenFormData(array $formData, array $fields)
    {
        foreach ($formData as $key => $value) {
            if ($key[0] == '_' || $value instanceof UploadedFile) {
                continue;
            } elseif (is_array($value) && count($value) > 0) {
                $fields[] = [
                    'name' => strip_tags($key),
                    'value' => null,
                ];
                $fields = $this->flattenFormData($value, $fields);
            } elseif (!empty($value)) {
                $name = is_numeric($key) ? null : strip_tags($key);
                $fields[] = [
                    'name' => $name,
                    'value' => (strip_tags($value)),
                ];
            }
        }

        return $fields;
    }


    /**
     * Send contact form data by email.
     * @return int
     * @throws \RuntimeException
     */
    public function send()
    {
        if (empty($this->assignation)) {
            throw new \RuntimeException("Can’t send a contact form without data.");
        }

        $this->message = $this->createMessage();

        /*
         * As this is a contact form
         * email receiver is website owner or custom.
         *
         * So you must return error email to receiver instead
         * of sender (who is your visitor).
         */
        $this->message->setTo($this->getReceiver());
        $this->message->setReturnPath($this->getReceiverEmail());

        /** @var UploadedFile $uploadedFile */
        foreach ($this->uploadedFiles as $uploadedFile) {
            $attachment = \Swift_Attachment::fromPath($uploadedFile->getRealPath())
                ->setFilename($uploadedFile->getClientOriginalName());
            $this->message->attach($attachment);
        }

        // Send the message
        return $this->mailer->send($this->message);
    }

    /**
     * Gets the value of redirectUrl.
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->redirectUrl;
    }

    /**
     * Sets the value of redirectUrl.
     *
     * @param string $redirectUrl the redirect url
     *
     * @return self
     */
    public function setRedirectUrl($redirectUrl)
    {
        $this->redirectUrl = $redirectUrl;

        return $this;
    }

    /**
     * Gets the value of maxFileSize.
     *
     * @return int
     */
    public function getMaxFileSize()
    {
        return $this->maxFileSize;
    }

    /**
     * Sets the value of maxFileSize.
     *
     * @param int $maxFileSize the max file size
     *
     * @return self
     */
    public function setMaxFileSize($maxFileSize)
    {
        $this->maxFileSize = (int) $maxFileSize;

        return $this;
    }

    /**
     * Gets the value of allowedMimeTypes.
     *
     * @return array
     */
    public function getAllowedMimeTypes()
    {
        return $this->allowedMimeTypes;
    }

    /**
     * Sets the value of allowedMimeTypes.
     *
     * @param array $allowedMimeTypes the allowed mime types
     *
     * @return self
     */
    public function setAllowedMimeTypes(array $allowedMimeTypes)
    {
        $this->allowedMimeTypes = $allowedMimeTypes;

        return $this;
    }

    /**
     * @return bool|null|string
     */
    public function getReceiver()
    {
        return (null !== parent::getReceiver() && parent::getReceiver() != "") ?
            (parent::getReceiver()) :
            ($this->settingsBag->get('email_sender'));
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     *
     * @return ContactFormManager
     */
    public function setOptions($options)
    {
        $this->options = $options;

        return $this;
    }
}
