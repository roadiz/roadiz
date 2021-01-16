<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\SettingGroup;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Group setting selector form field type.
 */
class SettingGroupType extends AbstractType
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new CallbackTransformer(
            function (SettingGroup $settingGroup = null) {
                if (null !== $settingGroup) {
                    // transform the array to a string
                    return $settingGroup->getId();
                }
                return null;
            },
            function ($id) {
                if (null !== $id) {
                    return $this->entityManager->find(SettingGroup::class, $id);
                }
                return null;
            }
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => [],
            'placeholder' => '---------',
        ]);

        /*
         * Use normalizer to populate choices from ChoiceType
         */
        $resolver->setNormalizer('choices', function (Options $options, $choices) {
            $groups = $this->entityManager->getRepository(SettingGroup::class)->findAll();
            /** @var SettingGroup $group */
            foreach ($groups as $group) {
                $choices[$group->getName()] = $group->getId();
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
        return 'setting_groups';
    }
}
