<?php
namespace RZ\Roadiz\Attribute\Form;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Attribute\Form\DataTransformer\AttributeGroupTransformer;
use RZ\Roadiz\Core\Entities\AttributeGroup;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Node types selector form field type.
 */
class AttributeGroupsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->addModelTransformer(new AttributeGroupTransformer($options['entityManager']));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'entityManager',
        ]);
        $resolver->setAllowedTypes('entityManager', [EntityManager::class]);
        $resolver->setNormalizer('choices', function (Options $options, $choices) {
            $criteria = [];
            $ordering = [
                'canonicalName' => 'ASC'
            ];
            $attributeGroups = $options['entityManager']
                ->getRepository(AttributeGroup::class)
                ->findBy($criteria, $ordering);

            /** @var AttributeGroup $attributeGroup */
            foreach ($attributeGroups as $attributeGroup) {
                $choices[$attributeGroup->getName()] = $attributeGroup->getId();
            }

            return $choices;
        });
    }
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'attribute_groups';
    }
}
