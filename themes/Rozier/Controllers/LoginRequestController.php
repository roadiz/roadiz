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

use RZ\Roadiz\CMS\Forms\LoginRequestForm;
use RZ\Roadiz\CMS\Traits\LoginRequestTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\Rozier\RozierApp;

class LoginRequestController extends RozierApp
{
    use LoginRequestTrait;

    /**
     * @param Request $request
     *
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Twig_Error_Runtime
     */
    public function indexAction(Request $request)
    {
        $form = $this->createForm(LoginRequestForm::class, null, [
            'entityManager' => $this->get('em'),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $this->sendConfirmationEmail(
                    $form,
                    $this->get('em'),
                    $this->get('logger'),
                    $this->get('urlGenerator')
                );
            }
            /*
             * Always go to confirm even if email is not valid
             * for avoiding database sniffing.
             */
            return $this->redirect($this->generateUrl(
                'loginRequestConfirmPage'
            ));
        }

        $this->assignation['form'] = $form->createView();

        return $this->render('login/request.html.twig', $this->assignation);
    }

    /**
     * @return Response
     * @throws \Twig_Error_Runtime
     */
    public function confirmAction()
    {
        return $this->render('login/requestConfirm.html.twig', $this->assignation);
    }
}
