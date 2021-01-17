<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\NodeSource;

use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

final class NodeSourceBaseType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', TextType::class, [
            'label' => 'title',
            'required' => false,
            'attr' => [
                'data-dev-name' => '{{ nodeSource.' . StringHandler::camelCase('title') . ' }}',
                'lang' => strtolower(str_replace('_', '-', $options['translation']->getLocale())),
                'dir' => $options['translation']->isRtl() ? 'rtl' : 'ltr',
            ],
            'constraints' => [
                new Length([
                    'max' => 255,
                ])
            ]
        ]);

        if ($options['publishable'] === true) {
            $builder->add('publishedAt', DateTimeType::class, [
                'label' => 'publishedAt',
                'required' => false,
                'attr' => [
                    'class' => 'rz-datetime-field',
                    'data-dev-name' => '{{ nodeSource.' . StringHandler::camelCase('publishedAt') . ' }}',
                ],
                'date_widget' => 'single_text',
                'date_format' => 'yyyy-MM-dd',
                'placeholder' => [
                    'hour' => 'hour',
                    'minute' => 'minute',
                ],
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'nodesourcebase';
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => false,
            'inherit_data' => true,
            'publishable' => false,
        ]);

        $resolver->setRequired('translation');

        $resolver->setAllowedTypes('publishable', 'boolean');
        $resolver->setAllowedTypes('translation', Translation::class);
    }
}
