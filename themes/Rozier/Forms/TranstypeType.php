<?php
declare(strict_types=1);

namespace Themes\Rozier\Forms;

use Doctrine\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use RZ\Roadiz\Core\Entities\NodeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class TranstypeType
 * @package Themes\Rozier\Forms
 */
class TranstypeType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'nodeTypeId',
            ChoiceType::class,
            [
                'choices' => $this->getAvailableTypes($options['em'], $options['currentType']),
                'label' => 'nodeType',
                'constraints' => [
                    new NotBlank(),
                ],
            ]
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'transtype';
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'compound' => true,
            'label' => false,
            'nodeName' => null,
            'attr' => [
                'class' => 'uk-form transtype-form',
            ],
        ]);

        $resolver->setRequired([
            'em',
            'currentType',
        ]);

        $resolver->setAllowedTypes('em', ObjectManager::class);
        $resolver->setAllowedTypes('currentType', NodeType::class);
    }

    /**
     * @param EntityManager $em
     * @param NodeType $currentType
     * @return array
     */
    protected function getAvailableTypes(EntityManager $em, NodeType $currentType)
    {
        $qb = $em->createQueryBuilder();
        $qb->select('n')
           ->from(NodeType::class, 'n')
           ->where($qb->expr()->neq('n.id', $currentType->getId()))
           ->orderBy('n.displayName', 'ASC');

        try {
            $types = $qb->getQuery()->getResult();

            $choices = [];
            /** @var NodeType $type */
            foreach ($types as $type) {
                $choices[$type->getDisplayName()] = $type->getId();
            }

            return $choices;
        } catch (NoResultException $e) {
            return [];
        }
    }
}
