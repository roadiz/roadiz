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
 * @file LoginController.php
 * @author Ambroise Maupate
 */

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Bags\SettingsBag;
use RZ\Roadiz\Core\Viewers\DocumentViewer;
use RZ\Roadiz\Utils\MediaFinders\SplashbasePictureFinder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Validator\Constraints\NotBlank;
use Themes\Rozier\RozierApp;

/**
 * Login controller
 */
class LoginController extends RozierApp
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $form = $this->buildLoginForm();

        $this->assignation['form'] = $form->createView();

        $helper = $this->get('securityAuthenticationUtils');

        $this->assignation['last_username'] = $helper->getLastUsername();
        $this->assignation['error'] = $helper->getLastAuthenticationError();

        return $this->render('login/login.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function checkAction(Request $request)
    {
        return $this->render('login/check.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function logoutAction(Request $request)
    {
        return $this->render('login/check.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function imageAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $response = new JsonResponse();

            if (false !== $document = SettingsBag::getDocument('login_image')) {
                $documentViewer = new DocumentViewer($document);

                $response->setData([
                    'url' => $documentViewer->getDocumentUrlByArray([
                        'noProcess' => true
                    ])
                ]);
            } else {
                $splash = new SplashbasePictureFinder();
                $response->setData($splash->getRandom());
            }

            return $response;
        }

        throw new ResourceNotFoundException();
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    private function buildLoginForm()
    {
        $defaults = [];

        $builder = $this->get('formFactory')
                        ->createNamedBuilder(null, 'form', $defaults, [])
                        ->add('_username', 'text', [
                            'label' => 'username',
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ])
                        ->add('_password', 'password', [
                            'label' => 'password',
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ])
                        ->add('_remember_me', 'checkbox', [
                            'label' => 'keep_me_logged_in',
                            'required' => false,
                            'attr' => [
                                'checked' => true
                            ],
                        ]);

        return $builder->getForm();
    }
}
