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
 * @file UsersSecurityController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers\Users;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Themes\Rozier\RozierApp;

/**
 * Provide user security views and forms.
 */
class UsersSecurityController extends RozierApp
{
    /**
     * @param Request $request
     * @param int     $userId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function securityAction(Request $request, $userId)
    {
        // Only user managers can review security
        $this->validateAccessForRole('ROLE_ACCESS_USERS');

        /** @var User $user */
        $user = $this->get('em')
                     ->find(User::class, (int) $userId);

        if ($user !== null) {
            $this->assignation['user'] = $user;
            $form = $this->buildEditSecurityForm($user);

            $form->handleRequest($request);

            if ($form->isValid()) {
                $this->editUserSecurity($form->getData(), $user, $request);
                $msg = $this->getTranslator()->trans(
                    'user.%name%.security.updated',
                    ['%name%' => $user->getUsername()]
                );

                $this->publishConfirmMessage($request, $msg);

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl(
                    'usersSecurityPage',
                    ['userId' => $user->getId()]
                ));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('users/security.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     *
     * @param  User   $user
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function buildEditSecurityForm(User $user)
    {
        $defaults = [
            'enabled' => $user->isEnabled(),
            'locked' => !$user->isAccountNonLocked(),
            'expiresAt' => $user->getExpiresAt(),
            'expired' => $user->getExpired(),
            'credentialsExpiresAt' => $user->getCredentialsExpiresAt(),
            'credentialsExpired' => $user->getCredentialsExpired(),
            'chroot' => ($user->getChroot() !== null) ? $user->getChroot()->getId() : null,
        ];

        /** @var FormBuilder $builder */
        $builder = $this->get('formFactory')
                        ->createNamedBuilder('source', 'form', $defaults);

        $builder->add('enabled', 'checkbox', [
                    'label' => 'user.enabled',
                    'required' => false,
                ])
                ->add('locked', 'checkbox', [
                    'label' => 'user.locked',
                    'required' => false,
                ])
                ->add('expiresAt', 'datetime', [
                    'label' => 'user.expiresAt',
                    'required' => false,
                    'years' => range(date('Y'), date('Y') + 2),
                    'date_widget' => 'single_text',
                    'date_format' => 'yyyy-MM-dd',
                    'attr' => [
                        'class' => 'rz-datetime-field',
                    ],
                    'placeholder' => [
                        'hour' => 'hour',
                        'minute' => 'minute',
                    ],
                ])
                ->add('expired', 'checkbox', [
                    'label' => 'user.force.expired',
                    'required' => false,
                ])
                ->add('credentialsExpiresAt', 'datetime', [
                    'label' => 'user.credentialsExpiresAt',
                    'required' => false,
                    'years' => range(date('Y'), date('Y') + 2),
                    'date_widget' => 'single_text',
                    'date_format' => 'yyyy-MM-dd',
                    'attr' => [
                        'class' => 'rz-datetime-field',
                    ],
                    'placeholder' => [
                        'hour' => 'hour',
                        'minute' => 'minute',
                    ],
                ])
                ->add('credentialsExpired', 'checkbox', [
                    'label' => 'user.force.credentialsExpired',
                    'required' => false,
                ]);

        if ($this->isGranted("ROLE_SUPERADMIN")) {
            $n = $user->getChroot();
            $n = ($n !== null) ? [$n] : [];
            $builder->add('chroot', new \RZ\Roadiz\CMS\Forms\NodesType($n, $this->get('em')), [
                'label' => 'chroot',
                'required' => false,
            ]);
        }

        return $builder->getForm();
    }

    protected function editUserSecurity(array $data, User $user, Request $request)
    {
        foreach ($data as $key => $value) {
            $setter = 'set' . ucwords($key);
            if ($key == "chroot") {
                if (count($value) > 1) {
                    $msg = $this->getTranslator()->trans('chroot.limited.one');
                    $this->publishErrorMessage($request, $msg);
                }
                if ($value !== null) {
                    $n = $this->get('em')->find(Node::class, $value[0]);
                    $user->$setter($n);
                } else {
                    $user->$setter(null);
                }
            } else {
                $user->$setter($value);
            }
        }

        $this->get('em')->flush();
    }
}
