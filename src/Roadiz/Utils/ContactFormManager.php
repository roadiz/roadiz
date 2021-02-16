<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils;

use RZ\Roadiz\CMS\Forms\Constraints\Recaptcha;
use RZ\Roadiz\CMS\Forms\HoneypotType;
use RZ\Roadiz\CMS\Forms\RecaptchaType;
use RZ\Roadiz\Core\Bags\Settings;
use RZ\Roadiz\Core\Exceptions\BadFormRequestException;
use RZ\Roadiz\Utils\UrlGenerators\DocumentUrlGeneratorInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Twig\Environment;

/**
 * @package RZ\Roadiz\Utils
 */
class ContactFormManager extends EmailManager
{
    protected string $formName = 'contact_form';
    protected ?array $uploadedFiles = null;
    protected ?string $redirectUrl = null;
    protected ?FormBuilderInterface $formBuilder = null;
    protected ?FormInterface $form = null;
    protected array $options = [];
    protected string $method = Request::METHOD_POST;
    protected bool $emailStrictMode = false;
    protected bool $useRealResponseCode = false;
    protected array $allowedMimeTypes = [
        'application/pdf',
        'application/x-pdf',
        'image/jpeg',
        'image/png',
        'image/gif',
    ];
    protected int $maxFileSize = 5242880; // 5MB
    protected FormFactoryInterface $formFactory;

    /**
     * DO NOT DIRECTLY USE THIS CONSTRUCTOR
     * USE 'contactFormManager' Factory Service
     *
     * @param Request                       $request
     * @param FormFactoryInterface          $formFactory
     * @param TranslatorInterface           $translator
     * @param Environment                   $templating
     * @param \Swift_Mailer                 $mailer
     * @param Settings                      $settingsBag
     * @param DocumentUrlGeneratorInterface $documentUrlGenerator
     *
     * @throws \ReflectionException
     */
    public function __construct(
        Request $request,
        FormFactoryInterface $formFactory,
        TranslatorInterface $translator,
        Environment $templating,
        \Swift_Mailer $mailer,
        Settings $settingsBag,
        DocumentUrlGeneratorInterface $documentUrlGenerator
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
     * @return string
     */
    public function getFormName(): string
    {
        return $this->formName;
    }

    /**
     * Use this method BEFORE withDefaultFields()
     *
     * @param string $formName
     * @return ContactFormManager
     */
    public function setFormName(string $formName): ContactFormManager
    {
        $this->formName = $formName;
        return $this;
    }

    /**
     * Use this method BEFORE withDefaultFields()
     *
     * @return $this
     */
    public function disableCsrfProtection()
    {
        $this->options['csrf_protection'] = false;

        return $this;
    }

    /**
     * @return FormInterface
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * Using the strict mode requires the "egulias/email-validator" library.
     *
     * Use this method BEFORE withDefaultFields()
     *
     * @param bool $emailStrictMode
     * @see https://symfony.com/doc/4.4/reference/constraints/Email.html#strict
     * @return $this
     */
    public function setEmailStrictMode(bool $emailStrictMode = true)
    {
        $this->emailStrictMode = $emailStrictMode;

        return $this;
    }
    /**
     * @return bool
     */
    public function isEmailStrictMode(): bool
    {
        return $this->emailStrictMode;
    }

    /**
     * Adds a email, name and message fields with their constraints.
     *
     * @param bool $useHoneypot
     * @return ContactFormManager $this
     */
    public function withDefaultFields($useHoneypot = true)
    {
        $this->getFormBuilder()->add('email', EmailType::class, [
                'label' => 'your.email',
                'constraints' => [
                    new NotNull(),
                    new NotBlank(),
                    new Email([
                        'message' => 'email.not.valid',
                        'mode' => $this->isEmailStrictMode() ? Email::VALIDATION_MODE_STRICT : Email::VALIDATION_MODE_LOOSE
                    ]),
                ],
            ])
            ->add('name', TextType::class, [
                'label' => 'your.name',
                'constraints' => [
                    new NotNull(),
                    new NotBlank(),
                ],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'your.message',
                'constraints' => [
                    new NotNull(),
                    new NotBlank(),
                ],
            ])
        ;

        if ($useHoneypot) {
            $this->withHoneypot();
        }

        return $this;
    }

    /**
     * Use this method AFTER withDefaultFields()
     *
     * @param string $honeypotName
     * @return $this
     */
    public function withHoneypot($honeypotName = 'eml')
    {
        $this->getFormBuilder()->add($honeypotName, HoneypotType::class);
        return $this;
    }

    /**
     * Use this method AFTER withDefaultFields()
     *
     * @param string $consentDescription
     * @return $this
     */
    public function withUserConsent($consentDescription = 'contact_form.user_consent')
    {
        $this->getFormBuilder()->add('consent', CheckboxType::class, [
            'label' => $consentDescription,
            'required' => true,
            'constraints' => [
                new NotBlank([
                    'message' => 'contact_form.must_consent_to_send',
                ]),
            ],
        ]);
        return $this;
    }

    /**
     * @return FormBuilderInterface
     */
    public function getFormBuilder()
    {
        if (null === $this->formBuilder) {
            $this->formBuilder = $this->formFactory
                ->createNamedBuilder($this->getFormName(), FormType::class, null, $this->options)
                ->setMethod($this->method);
        }
        return $this->formBuilder;
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
            $this->getFormBuilder()->add('recaptcha', RecaptchaType::class, [
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
            $this->request->getRequestFormat() === 'json' ||
            (count($this->request->getAcceptableContentTypes()) === 1 && $this->request->getAcceptableContentTypes()[0] === 'application/json') ||
            ($this->request->attributes->has('_format') && $this->request->attributes->get('_format') === 'json');

        if ($this->form->isSubmitted()) {
            if ($this->form->isSubmitted() && $this->form->isValid()) {
                try {
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
                            if ($this->request->hasPreviousSession()) {
                                /** @var Session $session */
                                $session = $this->request->getSession();
                                $session->getFlashBag()
                                    ->add('confirm', $this->translator->trans($this->successMessage));
                            }

                            $this->redirectUrl = $this->redirectUrl !== null ? $this->redirectUrl : $this->request->getUri();
                            return new RedirectResponse($this->redirectUrl);
                        }
                    } else {
                        $this->form->addError(new FormError('Contact form could not be sent.'));
                    }
                } catch (BadFormRequestException $e) {
                    if (null !== $e->getFieldErrored() && $this->form->has($e->getFieldErrored())) {
                        $this->form->get($e->getFieldErrored())->addError(new FormError($e->getMessage()));
                    } else {
                        $this->form->addError(new FormError($e->getMessage()));
                    }
                }
            }
            if ($returnJson) {
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
                /*
                 * BC: Still return 200 if form is not valid for Ajax forms
                 */
                return new JsonResponse(
                    $responseArray,
                    $this->useRealResponseCode() ? Response::HTTP_BAD_REQUEST : Response::HTTP_OK
                );
            }
        }
        return null;
    }

    protected function handleFiles()
    {
        $this->uploadedFiles = [];

        /*
         * Files values
         */
        foreach ($this->request->files as $files) {
            /**
             * @var string $name
             * @var UploadedFile|array $uploadedFile
             */
            foreach ($files as $name => $uploadedFile) {
                if (null !== $uploadedFile) {
                    if (is_array($uploadedFile)) {
                        /**
                         * @var string $singleName
                         * @var UploadedFile|array $singleUploadedFile
                         */
                        foreach ($uploadedFile as $singleName => $singleUploadedFile) {
                            if (is_array($singleUploadedFile)) {
                                /**
                                 * @var string $singleName2
                                 * @var UploadedFile $singleUploadedFile2
                                 */
                                foreach ($singleUploadedFile as $singleName2 => $singleUploadedFile2) {
                                    $this->addUploadedFile($singleName2, $singleUploadedFile2);
                                }
                            } else {
                                $this->addUploadedFile($singleName, $singleUploadedFile);
                            }
                        }
                    } else {
                        $this->addUploadedFile($name, $uploadedFile);
                    }
                }
            }
        }
    }

    /**
     * @param string $name
     * @param UploadedFile $uploadedFile
     *
     * @return $this
     * @throws BadFormRequestException
     */
    protected function addUploadedFile(string $name, UploadedFile $uploadedFile)
    {
        if (!$uploadedFile->isValid() ||
            !in_array($uploadedFile->getMimeType(), $this->allowedMimeTypes) ||
            $uploadedFile->getSize() > $this->maxFileSize) {
            throw new BadFormRequestException(
                $this->translator->trans('file.not.accepted'),
                Response::HTTP_FORBIDDEN,
                'danger',
                $name
            );
        } else {
            $this->uploadedFiles[$name] = $uploadedFile;
        }

        return $this;
    }

    /**
     * @param FormInterface $form
     *
     * @throws \Exception
     */
    protected function handleFormData(FormInterface $form)
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
                'name' => strip_tags((string) $key),
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
            'emailType' => $this->getEmailType(),
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
    protected function flattenFormData(array $formData, array $fields): array
    {
        foreach ($formData as $key => $value) {
            if ($key[0] == '_' || $value instanceof UploadedFile) {
                continue;
            } elseif (is_array($value) && count($value) > 0) {
                $fields[] = [
                    'name' => strip_tags((string) $key),
                    'value' => null,
                ];
                $fields = $this->flattenFormData($value, $fields);
            } elseif (null !== $value && !empty($value)) {
                if ($value instanceof \DateTimeInterface) {
                    $displayValue = $value->format('Y-m-d H:i:s');
                } else {
                    $displayValue = strip_tags(trim((string) $value));
                }
                $name = is_numeric($key) ? null : strip_tags(trim((string) $key));
                $fields[] = [
                    'name' => $name,
                    'value' => $displayValue,
                ];
            }
        }

        return $fields;
    }


    /**
     * Send contact form data by email.
     *
     * @return int The number of successful recipients. Can be 0 which indicates failure
     * @throws \RuntimeException
     */
    public function send(): int
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
     * @return bool|null|string
     */
    public function getReceiver()
    {
        return (null !== parent::getReceiver() && parent::getReceiver() != "") ?
            (parent::getReceiver()) :
            ($this->settingsBag->get('email_sender'));
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

    /**
     * @return bool
     */
    public function useRealResponseCode(): bool
    {
        return $this->useRealResponseCode;
    }

    /**
     * @param bool $useRealResponseCode Return a real 400 response if form is not valid.
     * @return ContactFormManager
     */
    public function setUseRealResponseCode(bool $useRealResponseCode): ContactFormManager
    {
        $this->useRealResponseCode = $useRealResponseCode;
        return $this;
    }
}
