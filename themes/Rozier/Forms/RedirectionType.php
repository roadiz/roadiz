<?php
declare(strict_types=1);

namespace Themes\Rozier\Forms;

use RZ\Roadiz\CMS\Forms\Constraints\UniqueEntity;
use RZ\Roadiz\Core\Entities\Redirection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

/**
 * @package Themes\Rozier\Forms
 */
class RedirectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('query', TextType::class, [
            'label' => (!$options['only_query']) ? 'redirection.query' : false,
            'attr' => [
                'placeholder' => $options['placeholder']
            ],
            'constraints' => [
                new NotNull(),
                new NotBlank(),
            ],
        ]);
        if ($options['only_query'] === false) {
            $builder->add('redirectUri', TextType::class, [
                'label' => 'redirection.redirect_uri',
                'required' => false,
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'redirection.type',
                'choices' => [
                    'redirection.moved_permanently' => Response::HTTP_MOVED_PERMANENTLY,
                    'redirection.moved_temporarily' => Response::HTTP_FOUND,
                ]
            ]);
        }
    }

    public function getBlockPrefix()
    {
        return 'redirection';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Redirection::class,
            'only_query' => false,
            'placeholder' => null,
            'attr' => [
                'class' => 'uk-form redirection-form',
            ],
            'constraints' => [
                new UniqueEntity([
                    'fields' => 'query',
                ])
            ]
        ]);
    }
}
