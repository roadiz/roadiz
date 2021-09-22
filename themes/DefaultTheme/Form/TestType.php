<?php
declare(strict_types=1);

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
                return null !== $dateAsString ? new \DateTime($dateAsString) : null;
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
