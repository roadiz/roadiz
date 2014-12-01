<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 *
 * @file ContactController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace Themes\DefaultTheme\Controllers;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Translation;

use Themes\DefaultTheme\DefaultThemeApp;

use RZ\Roadiz\CMS\Controllers\EntryPointsController;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
        $translation = $this->bindLocaleFromRoute($request, $_locale);
        $this->prepareThemeAssignation($node, $translation);

        /*
         * Create a custom contact form
         */
        $formBuilder = EntryPointsController::getContactFormBuilder(
            $request,
            true
        );
        $formBuilder->add('email', 'email', array(
                        'label'=>$this->getTranslator()->trans('your.email')
                    ))
                    ->add('name', 'text', array(
                        'label'=>$this->getTranslator()->trans('your.name')
                    ))
                    ->add('message', 'textarea', array(
                        'label'=>$this->getTranslator()->trans('your.message')
                    ))
                    ->add('callMeBack', 'checkbox', array(
                        'label'=>$this->getTranslator()->trans('call.me.back'),
                        'required' => false
                    ))
                    ->add('send', 'submit', array(
                        'label'=>$this->getTranslator()->trans('send.contact.form')
                    ));
        $form = $formBuilder->getForm();
        $this->assignation['contactForm'] = $form->createView();


        return new Response(
            $this->getTwig()->render('contact.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }
}
