<?php
declare(strict_types=1);

namespace Themes\Rozier\Forms;

use Doctrine\Persistence\ObjectManager;
use RZ\Roadiz\CMS\Forms\ColorType;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueTagName;
use RZ\Roadiz\Core\Entities\Tag;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class TagType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('tagName', TextType::class, [
                'label' => 'tagName',
                'help' => 'tag.tagName.help',
                'constraints' => [
                    new NotNull(),
                    new NotBlank(),
                    new UniqueTagName([
                        'currentValue' => $options['tagName'],
                    ]),
                    new Length([
                        'max' => 255,
                    ])
                ],
            ])

            ->add('locked', CheckboxType::class, [
                'label' => 'locked',
                'help' => 'tag.locked.help',
                'required' => false,
            ])
            ->add('visible', CheckboxType::class, [
                'label' => 'visible',
                'required' => false,
            ])
            ->add('color', ColorType::class, [
                'label' => 'tag.color',
                'required' => false,
            ])
            ->add('childrenOrder', ChoiceType::class, [
                'label' => 'tag.childrenOrder',
                'choices' => [
                    'position' => 'position',
                    'tagName' => 'tagName',
                    'createdAt' => 'createdAt',
                    'updatedAt' => 'updatedAt',
                ],
            ])
            ->add('childrenOrderDirection', ChoiceType::class, [
                'label' => 'tag.childrenOrderDirection',
                'choices' => [
                     'ascendant' => 'ASC',
                     'descendant' => 'DESC',
                ],
            ]);
    }

    public function getBlockPrefix()
    {
        return 'tag';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => false,
            'tagName' => '',
            'data_class' => Tag::class,
            'attr' => [
                'class' => 'uk-form tag-form',
            ],
        ]);

        $resolver->setAllowedTypes('tagName', 'string');
    }
}
