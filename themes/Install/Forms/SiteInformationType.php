<?php
/**
 * Copyright (c) 2018. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file SiteInformationType.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace Themes\Install\Forms;

use RZ\Roadiz\CMS\Forms\SeparatorType;
use RZ\Roadiz\CMS\Forms\ThemesType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Type;

class SiteInformationType extends AbstractType
{
    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $timeZoneList = include dirname(__DIR__) . '/Resources/import/timezones.php';

        $builder->add('site_name', TextType::class, [
                'required' => true,
                'label' => 'site_name',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('email_sender', EmailType::class, [
                'required' => true,
                'label' => 'email_sender',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('email_sender_name', TextType::class, [
                'required' => true,
                'label' => 'email_sender_name',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('seo_description', TextType::class, [
                'required' => false,
                'label' => 'meta_description',
            ])
            ->add('timezone', ChoiceType::class, [
                'choices' => $timeZoneList,
                'label' => 'timezone',
                'required' => true,
            ]);

        if (count($options['themes_config']) > 0) {
            $builder->add('separator_1', SeparatorType::class, [
                    'label' => 'themes.frontend.description',
                ])
                ->add('install_theme', CheckboxType::class, [
                    'required' => false,
                    'label' => 'install_theme',
                    'data' => true,
                ])
                ->add('className', ThemesType::class, [
                    'themes_config' => $options['themes_config'],
                    'label' => 'theme.selector',
                    'required' => true,
                    'constraints' => [
                        new NotNull(),
                        new Type('string'),
                    ],
                ])
            ;
        }
    }

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('themes_config');
        $resolver->setAllowedTypes('themes_config', 'array');
    }
}
