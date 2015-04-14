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
 * @file LoginRequestController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers;

use RZ\Roadiz\CMS\Forms\Constraints\ValidAccountEmail;
use RZ\Roadiz\Utils\Security\TokenGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Email;
use Themes\Rozier\RozierApp;

class LoginRequestController extends RozierApp
{
    /**
     * Time to live for a confirmation token
     */
    const CONFIRMATION_TTL = 300;

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $form = $this->buildLoginRequestForm();

        $form->handleRequest();

        if ($form->isValid()) {
            $user = $this->getService('em')
                         ->getRepository('RZ\Roadiz\Core\Entities\User')
                         ->findOneByEmail($form->getData()['email']);

            if (null !== $user) {
                if (!$user->isPasswordRequestNonExpired(LoginRequestController::CONFIRMATION_TTL)) {
                    $tokenGenerator = new TokenGenerator($this->getService('logger'));
                    $user->setPasswordRequestedAt(new \DateTime());
                    $user->setConfirmationToken($tokenGenerator->generateToken());
                    $this->getService('em')->flush();
                    $user->getViewer()->sendPasswordResetLink($this->getService('urlGenerator'));

                    return $this->redirect($this->generateUrl(
                        'loginRequestConfirmPage'
                    ));
                } else {
                    $this->assignation['error'] = $this->getTranslator()->trans('a.confirmation.email.has.already.be.sent');
                }
            }
        }

        $this->assignation['form'] = $form->createView();

        return $this->render('login/request.html.twig', $this->assignation);
    }

    /**
     * @param  Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function confirmAction(Request $request)
    {
        return $this->render('login/requestConfirm.html.twig', $this->assignation);
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Group $group
     * @param RZ\Roadiz\Core\Entities\Role  $role
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildLoginRequestForm()
    {
        $builder = $this->getService('formFactory')
                        ->createBuilder('form')
                        ->add('email', 'email', [
                            'required' => true,
                            'label' => $this->getTranslator()->trans('your.account.email'),
                            'constraints' => [
                                new Email([
                                    'message' => $this->getTranslator()->trans('email.invalid'),
                                    'checkMX' => true,
                                ]),
                                new ValidAccountEmail([
                                    'entityManager' => $this->getService('em'),
                                    'message' => $this->getTranslator()->trans('%email%.email.does.not.exist.in.user.account.database'),
                                ]),
                            ],
                        ]);

        return $builder->getForm();
    }
}
