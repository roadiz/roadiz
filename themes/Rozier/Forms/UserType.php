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
 * @file UserType.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Forms;

use RZ\Roadiz\CMS\Forms\Constraints\UniqueEmail;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueUsername;
use RZ\Roadiz\CMS\Forms\Constraints\ValidFacebookName;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 *
 */
class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('email', 'email', [
                'label' => 'email',
                'constraints' => [
                    new NotBlank(),
                    new UniqueEmail([
                        'entityManager' => $options['em'],
                        'currentValue' => $options['email'],
                    ]),
                ],
            ])
            ->add('username', 'text', [
                'label' => 'username',
                'constraints' => [
                    new NotBlank(),
                    new UniqueUsername([
                        'entityManager' => $options['em'],
                        'currentValue' => $options['username'],
                    ]),
                ],
            ])
            ->add('plainPassword', 'repeated', [
                'type' => 'password',
                'invalid_message' => 'password.must.match',
                'first_options' => [
                    'label' => 'password',
                ],
                'second_options' => [
                    'label' => 'passwordVerify',
                ],
                'required' => false,
            ])
            ->add('firstName', 'text', [
                'label' => 'firstName',
                'required' => false,
            ])
            ->add('lastName', 'text', [
                'label' => 'lastName',
                'required' => false,
            ])
            ->add('facebookName', 'text', [
                'label' => 'facebookName',
                'required' => false,
                'constraints' => [
                    new ValidFacebookName(),
                ],
            ])
            ->add('company', 'text', [
                'label' => 'company',
                'required' => false,
            ])
            ->add('job', 'text', [
                'label' => 'job',
                'required' => false,
            ])
            ->add('birthday', 'date', [
                'label' => 'birthday',
                'required' => false,
                'years' => range(1920, date('Y') - 6),
            ]);
    }

    public function getName()
    {
        return 'user';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'compound' => true,
            'label' => false,
            'email' => '',
            'username' => '',
            'data_class' => 'RZ\Roadiz\Core\Entities\User',
            'attr' => [
                'class' => 'uk-form user-form',
            ],
        ]);

        $resolver->setRequired([
            'em',
        ]);

        $resolver->setAllowedTypes([
            'em' => 'Doctrine\Common\Persistence\ObjectManager',
            'email' => 'string',
            'username' => 'string',
        ]);
    }
}
