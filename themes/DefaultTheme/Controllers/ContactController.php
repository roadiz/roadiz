<?php
/*
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
 *
 *
 * @file ContactController.php
 * @author Ambroise Maupate
 */
namespace Themes\DefaultTheme\Controllers;

use RZ\Roadiz\CMS\Controllers\EntryPointsController;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\DefaultTheme\DefaultThemeApp;
use \RZ\Roadiz\Core\Exceptions\NoTranslationAvailableException;

/**
 * Contact form page.
 */
class ContactController extends DefaultThemeApp
{

    public function indexAction(
        Request $request,
        Node $node = null,
        Translation $translation = null,
        $_locale = null,
        $_route = null
    ) {
        /*
         * You must catch NoTranslationAvailableException if
         * user visit a non-available translation.
         */
        try {
            $translation = $this->bindLocaleFromRoute($request, $_locale);
            $this->prepareThemeAssignation($node, $translation);

            /*
             * Create a custom contact form
             */
            $formBuilder = EntryPointsController::getContactFormBuilder(
                $request,
                true,
                null,
                null,
                null
            );
            $formBuilder->add('email', 'email', array(
                            'label' => $this->getTranslator()->trans('your.email'),
                        ))
                        ->add('name', 'text', array(
                            'label' => $this->getTranslator()->trans('your.name'),
                        ))
                        ->add('message', 'textarea', array(
                            'label' => $this->getTranslator()->trans('your.message'),
                        ))
                        ->add('callMeBack', 'checkbox', array(
                            'label' => $this->getTranslator()->trans('call.me.back'),
                            'required' => false,
                        ))
                        ->add('document', 'file', array(
                            'label' => $this->getTranslator()->trans('document'),
                            'required' => false,
                        ))
                        ->add('send', 'submit', array(
                            'label' => $this->getTranslator()->trans('send.contact.form'),
                        ));
            $form = $formBuilder->getForm();

            $this->assignation['contactForm'] = $form->createView();

            /*
             * Assign route to check current menu entry in navigation.html.twig
             */
            $this->assignation['route'] = $_route;

            return new Response(
                $this->getTwig()->render('contact.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } catch (NoTranslationAvailableException $e) {
            return $this->throw404();
        }
    }
}
