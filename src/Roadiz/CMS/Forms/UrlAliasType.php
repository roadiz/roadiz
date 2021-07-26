<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms;

use Doctrine\Persistence\ManagerRegistry;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueNodeName;
use RZ\Roadiz\CMS\Forms\DataTransformer\TranslationTransformer;
use RZ\Roadiz\Core\Entities\UrlAlias;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class UrlAliasType extends AbstractType
{
    private ManagerRegistry $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('alias', TextType::class, [
            'label' => false,
            'attr' => [
                'placeholder' => 'urlAlias',
            ],
            'constraints' => [
                new NotNull(),
                new NotBlank(),
                new UniqueNodeName(),
            ]
        ]);
        if ($options['with_translation']) {
            $builder->add('translation', TranslationsType::class, [
                'label' => false,
                'mapped' => false,
            ]);
            $builder->get('translation')->addModelTransformer(new TranslationTransformer(
                $this->managerRegistry
            ));
        }
    }

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', UrlAlias::class);
        $resolver->setDefault('with_translation', false);
        $resolver->setAllowedTypes('with_translation', ['bool']);
    }
}
