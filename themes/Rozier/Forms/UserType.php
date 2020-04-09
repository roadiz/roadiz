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

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueEmail;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueUsername;
use RZ\Roadiz\CMS\Forms\CreatePasswordType;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 *
 */
class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('email', EmailType::class, [
                'label' => 'email',
                'constraints' => [
                    new NotBlank(),
                    new UniqueEmail([
                        'entityManager' => $options['em'],
                        'currentValue' => $options['email'],
                    ]),
                    new UniqueUsername([
                        'entityManager' => $options['em'],
                        'currentValue' => $options['username'],
                    ]),
                    new Length([
                        'max' => 200,
                    ])
                ],
            ])
            ->add('username', TextType::class, [
                'label' => 'username',
                'constraints' => [
                    new NotBlank(),
                    new UniqueUsername([
                        'entityManager' => $options['em'],
                        'currentValue' => $options['username'],
                    ]),
                    new UniqueEmail([
                        'entityManager' => $options['em'],
                        'currentValue' => $options['email'],
                    ]),
                    new Length([
                        'max' => 200
                    ])
                ],
            ])
            ->add('plainPassword', CreatePasswordType::class, [
                'invalid_message' => 'password.must.match',
            ])
        ;
    }

    public function getBlockPrefix()
    {
        return 'user';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'compound' => true,
            'label' => false,
            'email' => '',
            'username' => '',
            'data_class' => User::class,
            'attr' => [
                'class' => 'uk-form user-form',
            ],
        ]);

        $resolver->setRequired([
            'em',
        ]);

        $resolver->setAllowedTypes('em', EntityManagerInterface::class);
        $resolver->setAllowedTypes('email', 'string');
        $resolver->setAllowedTypes('username', 'string');
    }
}
