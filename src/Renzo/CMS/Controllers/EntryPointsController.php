<?php
/**
 * Copyright REZO ZERO 2014
 *
 *
 * @file EntryPointsController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\CMS\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Bags\SettingsBag;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Security\Core\SecurityContext;

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

use \InlineStyle\InlineStyle;

/**
 * Defines entry points for RZCMS v3.
 */
class EntryPointsController extends AppController
{
    const CONTACT_FORM_TOKEN_INTENTION = 'contact_form';

    private static $mandatoryContactFields = array(
        'email',
        'message'
    );

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
        return RENZO_ROOT.'/src/Renzo/Core/Resources';
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

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param string                                   $method
     *
     * @return boolean | array  Return true if request is valid, else return error array
     */
    protected function validateRequest(Request $request, $method = 'POST')
    {
        if ($request->getMethod() != $method ||
            !is_array($request->get('form'))) {

            return array(
                'statusCode'   => Response::HTTP_FORBIDDEN,
                'status'       => 'danger',
                'responseText' => 'Wrong request'
            );
        }
        if (!$this->getService('csrfProvider')
                ->isCsrfTokenValid(static::CONTACT_FORM_TOKEN_INTENTION, $request->get('form')['_token'])) {

            return array(
                'statusCode'   => Response::HTTP_FORBIDDEN,
                'status'       => 'danger',
                'responseText' => 'Bad token'
            );
        }

        return true;
    }

    /**
     * Handles contact forms requests.
     *
     * @param  Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function contactFormAction(Request $request, $_locale = null)
    {
        if (true !== $validation = $this->validateRequest($request)) {

            return new Response(
                json_encode($validation),
                Response::HTTP_FORBIDDEN,
                array('content-type' => 'application/javascript')
            );
        }
        $canSend = true;
        $responseArray = array(
            'statusCode'   => Response::HTTP_OK,
            'status'       => 'success',
            'field_error'  => null
        );

        foreach (static::$mandatoryContactFields as $mandatoryField) {
            if (empty($request->get('form')[$mandatoryField])) {

                $responseArray['statusCode'] = Response::HTTP_FORBIDDEN;
                $responseArray['status'] = 'danger';
                $responseArray['field_error'] = $mandatoryField;
                $responseArray['message'] = $this->getTranslator()->trans(
                    '%field%.is.mandatory',
                    array('%field%' => ucwords($mandatoryField))
                );

                $request->getSession()->getFlashBag()->add('error', $responseArray['message']);
                $this->getService('logger')->error($responseArray['message']);

                $canSend = false;
            }
        }

        if (false === filter_var($request->get('form')['email'], FILTER_VALIDATE_EMAIL)) {
            $responseArray['statusCode'] = Response::HTTP_FORBIDDEN;
            $responseArray['status'] = 'danger';
            $responseArray['field_error'] = 'email';
            $responseArray['message'] = $this->getTranslator()->trans(
                'email.not.valid'
            );

            $request->getSession()->getFlashBag()->add('error', $responseArray['message']);
            $this->getService('logger')->error($responseArray['message']);

            $canSend = false;
        }

        /*
         * if no error, create Email
         */
        if ($canSend) {

            $receiver = SettingsBag::get('email_sender');

            $assignation = array(
                'mailContact' => SettingsBag::get('email_sender'),
                'title' => $this->getTranslator()->trans(
                    'new.contact.form.%site%',
                    array('%site%'=>SettingsBag::get('site_name'))
                ),
                'email' => $request->get('form')['email'],
                'fields' => array()
            );

            foreach ($request->get('form') as $key => $value) {
                if ($key[0] == '_') {
                    continue;
                } elseif (!empty($value)) {

                    $assignation['fields'][] = array(
                        'name' => strip_tags($key),
                        'value' => (strip_tags($value))
                    );
                }
            }
            /*
             *  Date
             */
            $assignation['fields'][] = array(
                'name' => $this->getTranslator()->trans('date'),
                'value' => (new \DateTime())->format('Y-m-d H:i:s')
            );
            /*
             *  IP
             */
            $assignation['fields'][] = array(
                'name' => $this->getTranslator()->trans('ip.address'),
                'value' => $request->getClientIp()
            );

            $this->sendContactForm($assignation, $receiver);

            $responseArray['message'] = $this->getTranslator()->trans(
                'form.successfully.sent'
            );

            $request->getSession()->getFlashBag()->add('confirm', $responseArray['message']);
            $this->getService('logger')->info($responseArray['message']);
        }

        /*
         * If no AJAX and a redirect URL is present,
         * just redirect.
         */
        if (!empty($request->get('form')['_redirect'])) {

            $response = new RedirectResponse($request->get('form')['_redirect']);
            $response->prepare($request);

            return $response->send();

        } else {

            return new Response(
                json_encode($responseArray),
                Response::HTTP_OK,
                array('content-type' => 'application/javascript')
            );
        }

    }

    /**
     * Generate a form-builder for contact forms.
     *
     * For example in your contact page controller :
     *
     * <pre>
     * use RZ\Renzo\CMS\Controllers\EntryPointsController;
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
     * @param Symfony\Component\HttpFoundation\Request $request Contact page request
     * @param boolean                                  $redirect Redirect to contact page after sending?
     *
     * @return Symfony\Component\Form\FormBuilder
     */
    public static function getContactFormBuilder(Request $request, $redirect = true)
    {
        $action = Kernel::getService('urlGenerator')
                        ->generate('contactFormLocaleAction', array(
                            '_locale' => $request->getLocale()
                        ));


        $builder = Kernel::getService('formFactory')
            ->createBuilder('form', null, array(
                'csrf_provider' => Kernel::getService('csrfProvider'),
                'intention' => static::CONTACT_FORM_TOKEN_INTENTION,
                'attr' => array(
                    'id' => 'contactForm'
                )
            ))
            ->setMethod('POST')
            ->setAction($action)
            ->add('_action', 'hidden', array(
                'data' => 'contact'
            ));

        if (true === $redirect) {
            $builder->add('_redirect', 'hidden', array(
                'data' => strip_tags($request->getURI())
            ));
        }

        return $builder;
    }

    /**
     * Send a contact form by Email.
     *
     * @param  array $assignation
     * @param  string $receiver
     *
     * @return boolean
     */
    protected function sendContactForm($assignation, $receiver)
    {
        $emailBody = $this->getTwig()->render('forms/contactForm.html.twig', $assignation);
        /*
         * inline CSS
         */
        $htmldoc = new InlineStyle($emailBody);
        $htmldoc->applyStylesheet(file_get_contents(
            RENZO_ROOT."/src/Renzo/Core/Resources/css/transactionalStyles.css"
        ));

        // Create the message
        $message = \Swift_Message::newInstance()
            // Give the message a subject
            ->setSubject($this->getTranslator()->trans(
                'new.contact.form.%site%',
                array('%site%'=>SettingsBag::get('site_name'))
            ))
            // Set the From address with an associative array
            ->setFrom(array($assignation['email']))
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
}
