<?php
/*
 * Copyright REZO ZERO 2014
 *
 * Description
 *
 * @file LoginController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */

namespace Themes\Rozier\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Document;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Utils\SplashbasePictureFinder;

use Themes\Rozier\RozierApp;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\SecurityContext;

use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * Login controller
 */
class LoginController extends RozierApp
{

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $form = $this->buildLoginForm();

        $this->assignation['form'] = $form->createView();

        $splash = new SplashbasePictureFinder();
        $this->assignation['splash'] = $splash->getRandom();

        $session = $this->getService('session');
        // get the login error if there is one
        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
            $session->remove(SecurityContext::AUTHENTICATION_ERROR);
        }

        $this->assignation['error'] = $error;

        return new Response(
            $this->getTwig()->render('login/login.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function checkAction(Request $request)
    {
        return new Response(
            $this->getTwig()->render('login/check.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function logoutAction(Request $request)
    {
        return new Response(
            $this->getTwig()->render('login/check.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    private function buildLoginForm()
    {
        $defaults = array();

        $builder = $this->getService('formFactory')
            ->createNamedBuilder(null, 'form', $defaults, array())
            ->add('_username', 'text', array(
                'label' => $this->getTranslator()->trans('username'),
                'constraints' => array(
                    new NotBlank()
                )
            ))
            ->add('_password', 'password', array(
                'label' => $this->getTranslator()->trans('password'),
                'constraints' => array(
                    new NotBlank()
                )
            ));

        return $builder->getForm();
    }
}
