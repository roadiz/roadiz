<?php
declare(strict_types=1);

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
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function resetAction(Request $request, string $token)
    {
        /** @var User|null $user */
        $user = $this->getUserByToken($this->get('em'), $token);

        if (null !== $user) {
            $form = $this->createForm(LoginResetForm::class, null, [
                'token' => $token,
                'confirmationTtl' => User::CONFIRMATION_TTL,
            ]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
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
