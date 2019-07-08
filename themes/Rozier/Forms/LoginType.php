<?php
/**
 * Copyright © 2019, Ambroise Maupate and Julien Blanchet
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
 * Except as contained in this notice, the name of the roadiz shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file LoginType.php
 * @author Ambroise Maupate
 *
 */
declare(strict_types=1);

namespace Themes\Rozier\Forms;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class LoginType extends AbstractType
{
    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('_username', TextType::class, [
            'label' => 'username',
            'attr' => [
                'autocomplete' => 'username'
            ],
            'constraints' => [
                new NotBlank(),
            ],
        ])
        ->add('_password', PasswordType::class, [
            'label' => 'password',
            'attr' => [
                'autocomplete' => 'current-password'
            ],
            'constraints' => [
                new NotBlank(),
            ],
        ])
        ->add('_remember_me', CheckboxType::class, [
            'label' => 'keep_me_logged_in',
            'required' => false,
            'attr' => [
                'checked' => true
            ],
        ]);

        if ($options['requestStack']->getMasterRequest()->query->has('_home')) {
            $builder->add('_target_path', HiddenType::class, [
                'data' => $options['urlGenerator']->generate('adminHomePage')
            ]);
        }
    }

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setNormalizer('action', function (Options $options) {
            return $options['urlGenerator']->generate('loginCheckPage');
        });
        $resolver->setRequired('urlGenerator');
        $resolver->setRequired('requestStack');
        $resolver->setAllowedTypes('urlGenerator', [UrlGeneratorInterface::class]);
        $resolver->setAllowedTypes('requestStack', [RequestStack::class]);
    }

    /**
     * @inheritDoc
     */
    public function getBlockPrefix()
    {
        /*
         * No prefix for firewall to catch username and password from request.
         */
        return null;
    }
}
