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
 * @file LoginResetController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers;

use RZ\Roadiz\CMS\Forms\LoginResetForm;
use RZ\Roadiz\CMS\Traits\LoginResetTrait;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\HttpFoundation\Request;
use Themes\Rozier\RozierApp;

class LoginResetController extends RozierApp
{
    use LoginResetTrait;

    /**
     * @param Request $request
     * @param string  $token
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Twig_Error_Runtime
     */
    public function resetAction(Request $request, $token)
    {
        /** @var User $user */
        $user = $this->getUserByToken($this->get('em'), $token);

        if (null !== $user) {
            $form = $this->createForm(LoginResetForm::class, null, [
                'token' => $token,
                'confirmationTtl' => User::CONFIRMATION_TTL,
                'entityManager' => $this->get('em'),
            ]);
            $form->handleRequest($request);

            if ($form->isValid()) {
                if ($this->updateUserPassword($form, $user, $this->get('em'))) {
                    return $this->redirect($this->generateUrl(
                        'loginResetConfirmPage'
                    ));
                }
            }
            $this->assignation['form'] = $form->createView();
        } else {
            $this->assignation['error'] = $this->getTranslator()->trans('confirmation.token.is.invalid');
        }

        return $this->render('login/reset.html.twig', $this->assignation);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function confirmAction()
    {
        return $this->render('login/resetConfirm.html.twig', $this->assignation);
    }
}
