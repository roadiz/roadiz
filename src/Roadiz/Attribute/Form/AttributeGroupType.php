<?php
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Form;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueEntity;
use RZ\Roadiz\Core\Entities\AttributeGroup;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AttributeGroupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', TextType::class, [
            'label' => 'attribute_group.form.name'
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', AttributeGroup::class);
        $resolver->setRequired('entityManager')
            ->setAllowedTypes('entityManager', [EntityManagerInterface::class]);

        $resolver->addNormalizer('constraints', function (Options $options) {
            return [
                new UniqueEntity([
                    'fields' => ['name'],
                    'entityManager' => $options['entityManager']
                ])
            ];
        });
    }
}
