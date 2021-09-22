<?php
declare(strict_types=1);

namespace Themes\DefaultTheme\Controllers;

use RZ\Roadiz\Core\AbstractEntities\TranslationInterface;
use RZ\Roadiz\Core\Entities\Node;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\File;
use Themes\DefaultTheme\DefaultThemeApp;

/**
 * @package Themes\DefaultTheme\Controllers
 */
class ContactController extends DefaultThemeApp
{
    /**
     * @param Request          $request
     * @param Node|null        $node
     * @param TranslationInterface|null $translation
     * @param string           $_locale
     * @param null             $_route
     *
     * @return null|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(
        Request $request,
        Node $node = null,
        TranslationInterface $translation = null,
        string $_locale = "en",
        $_route = null
    ) {
        $translation = $this->bindLocaleFromRoute($request, $_locale);
        $this->prepareThemeAssignation($node, $translation);

        $contactFormManager = $this->createContactFormManager()
           /*
            * Disable CSRF protection if using Varnish
            */
           //->disableCsrfProtection()
           // Use Honeypot
           ->withDefaultFields(true)
           ->withGoogleRecaptcha()
           ->setRedirectUrl($this->generateUrl('thanksPageLocale', [
               '_locale' => $_locale
           ]))
        ;

        /*
         * Create a custom contact form
         */
        $formBuilder = $contactFormManager->getFormBuilder();
        $formBuilder->add('callMeBack', CheckboxType::class, [
                'label' => 'call.me.back',
                'required' => false,
            ])
            ->add('document', FileType::class, [
                'label' => 'document',
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => $contactFormManager->getMaxFileSize(),
                        'mimeTypes' => $contactFormManager->getAllowedMimeTypes(),
                    ]),
                ],
            ])
            ->add('send', SubmitType::class, [
                'label' => 'send.contact.form',
            ])
        ;

        if (null !== $response = $contactFormManager->handle()) {
            return $response;
        }

        $form = $contactFormManager->getForm();
        $this->assignation['contactForm'] = $form->createView();

        /*
         * Assign route to check current menu entry in navigation.html.twig
         */
        $this->assignation['route'] = $_route;
        $response = $this->render('pages/contact.html.twig', $this->assignation);

        return $this->makeResponseCachable($request, $response, 2);
    }

    /**
     * @param Request $request
     * @param string $_locale
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function thankAction(
        Request $request,
        string $_locale = "en"
    ) {
        $translation = $this->bindLocaleFromRoute($request, $_locale);
        $this->prepareThemeAssignation(null, $translation);

        $response = $this->render('pages/thank.html.twig', $this->assignation);

        return $this->makeResponseCachable($request, $response, 2);
    }
}
