<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms;

use Doctrine\Persistence\ManagerRegistry;
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
    protected ManagerRegistry $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'showInvisible' => false,
        ]);
        $resolver->setAllowedTypes('showInvisible', ['boolean']);
        $resolver->setNormalizer('choices', function (Options $options, $choices) {
            $criteria = [];
            if ($options['showInvisible'] === false) {
                $criteria['visible'] = true;
            }
            $nodeTypes = $this->managerRegistry->getRepository(NodeType::class)->findBy($criteria);

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
