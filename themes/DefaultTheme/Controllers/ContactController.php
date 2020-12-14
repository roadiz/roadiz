<?php
/**
 * Copyright © 2016, Ambroise Maupate and Julien Blanchet
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
 * @file ContactController.php
 * @author Ambroise Maupate
 */
namespace Themes\DefaultTheme\Controllers;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Exceptions\NoTranslationAvailableException;
use RZ\Roadiz\Preview\PreviewResolverInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
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
     * @param Translation|null $translation
     * @param string           $_locale
     * @param null             $_route
     *
     * @return null|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(
        Request $request,
        Node $node = null,
        Translation $translation = null,
        $_locale = "en",
        $_route = null
    ) {
        /*
         * You must catch NoTranslationAvailableException if
         * user visit a non-available translation.
         */
        try {
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
                        ]);

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
        } catch (NoTranslationAvailableException $e) {
            throw new ResourceNotFoundException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @param Request $request
     * @param string $_locale
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function thankAction(
        Request $request,
        $_locale = "en"
    ) {
        $translation = $this->bindLocaleFromRoute($request, $_locale);
        $this->prepareThemeAssignation(null, $translation);

        $response = $this->render('pages/thank.html.twig', $this->assignation);

        return $this->makeResponseCachable($request, $response, 2);
    }
}
