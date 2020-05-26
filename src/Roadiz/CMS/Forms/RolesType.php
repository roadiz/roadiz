<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\Role;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Roles selector form field type.
 */
class RolesType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'roles' => new ArrayCollection(),
            'multiple' => false,
        ]);

        $resolver->setRequired('authorizationChecker');
        $resolver->setAllowedTypes('authorizationChecker', [AuthorizationCheckerInterface::class]);
        $resolver->setRequired('entityManager');
        $resolver->setAllowedTypes('entityManager', [EntityManager::class]);
        $resolver->setAllowedTypes('multiple', ['bool']);
        $resolver->setAllowedTypes('roles', [Collection::class]);

        /*
         * Use normalizer to populate choices from ChoiceType
         */
        $resolver->setNormalizer('choices', function (Options $options, $choices) {
            /** @var EntityManager $entityManager */
            $entityManager = $options['entityManager'];
            $roles = $entityManager->getRepository(Role::class)->findAll();

            /** @var Role $role */
            foreach ($roles as $role) {
                if ($options['authorizationChecker']->isGranted($role->getRole()) &&
                    !$options['roles']->contains($role)) {
                    $choices[$role->getRole()] = $role->getId();
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
        return 'roles';
    }
}
