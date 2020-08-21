<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\NodeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Node types selector form field type.
 */
class NodeTypesType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'showInvisible' => false,
        ]);
        $resolver->setRequired([
            'entityManager',
        ]);

        $resolver->setAllowedTypes('entityManager', [EntityManager::class]);
        $resolver->setAllowedTypes('showInvisible', ['boolean']);
        $resolver->setNormalizer('choices', function (Options $options, $choices) {
            $criteria = [];
            if ($options['showInvisible'] === false) {
                $criteria['visible'] = true;
            }
            $nodeTypes = $options['entityManager']->getRepository(NodeType::class)->findBy($criteria);

            /** @var NodeType $nodeType */
            foreach ($nodeTypes as $nodeType) {
                $choices[$nodeType->getDisplayName()] = $nodeType->getId();
            }
            ksort($choices);

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
        return 'node_types';
    }
}
