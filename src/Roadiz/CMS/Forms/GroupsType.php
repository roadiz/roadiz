<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms;

use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use RZ\Roadiz\Core\Entities\Group;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Group selector form field type.
 */
class GroupsType extends AbstractType
{
    protected AuthorizationCheckerInterface $authorizationChecker;
    protected ManagerRegistry $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new CallbackTransformer(function ($modelToForm) {
            if (null !== $modelToForm) {
                if ($modelToForm instanceof Collection) {
                    $modelToForm = $modelToForm->toArray();
                }
                return array_map(function (Group $group) {
                    return $group->getId();
                }, $modelToForm);
            }
            return null;
        }, function ($formToModels) {
            if (null === $formToModels || (is_array($formToModels) && count($formToModels) === 0)) {
                return [];
            }
            return $this->managerRegistry->getRepository(Group::class)->findBy([
                'id' => $formToModels
            ]);
        }));
    }


    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([]);

        /*
         * Use normalizer to populate choices from ChoiceType
         */
        $resolver->setNormalizer('choices', function (Options $options, $choices) {
            $groups = $this->managerRegistry->getRepository(Group::class)->findAll();

            /** @var Group $group */
            foreach ($groups as $group) {
                if ($this->authorizationChecker->isGranted($group)) {
                    $choices[$group->getName()] = $group->getId();
                }
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
        return 'groups';
    }
}
