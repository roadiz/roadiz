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
 * @file TestForm.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace Themes\DefaultTheme\Form;

use RZ\Roadiz\CMS\Forms\ColorType;
use RZ\Roadiz\CMS\Forms\MarkdownType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class TestType extends AbstractType
{
    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', TextType::class, [
                'help' => 'Nullam id dolor id nibh ultricies vehicula ut id elit. Donec ullamcorper nulla non metus auctor fringilla.'
            ])
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'help' => 'Etiam porta sem malesuada magna mollis euismod. Nullam quis risus eget urna mollis ornare vel eu leo.',
                'attr' => [
                    'class' => 'rz-date-field',
                ],
                'placeholder' => '',
            ])
            ->add('content', MarkdownType::class, [
                'help' => 'Nullam id dolor id nibh ultricies vehicula ut id elit. Donec ullamcorper nulla non metus auctor fringilla.'
            ])
            ->add('choice', ChoiceType::class, [
                'choices' => [
                    'Fusce' => 'Fusce',
                    'Inceptos Bibendum' => 'Inceptos Bibendum',
                ],
                'help' => 'Nullam id dolor id nibh ultricies vehicula ut id elit. Donec ullamcorper nulla non metus auctor fringilla.'
            ])
            ->add('color', ColorType::class, [
                'help' => 'Nullam id dolor id nibh ultricies vehicula ut id elit. Donec ullamcorper nulla non metus auctor fringilla.'
            ])
            ->add('choice_bullet', ChoiceType::class, [
                'expanded' => true,
                'choices' => [
                    'Fusce' => 'Fusce',
                    'Inceptos Bibendum' => 'Inceptos Bibendum',
                ],
                'help' => 'Nullam id dolor id nibh ultricies vehicula ut id elit. Donec ullamcorper nulla non metus auctor fringilla.'
            ])
        ;

        $builder->get('date')->addModelTransformer(new CallbackTransformer(
            function ($dateAsString) {
                return new \DateTime($dateAsString);
            },
            function (\DateTimeInterface $dateTime = null) {
                if (null === $dateTime) {
                    return new \DateTime();
                }
                return $dateTime->format('Y-m-d H:i:s');
            }
        ));
    }
}
