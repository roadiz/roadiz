<?php
declare(strict_types=1);

namespace Themes\Rozier\Traits;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueNodeName;
use RZ\Roadiz\CMS\Forms\NodeTypesType;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Utils\Node\NodeFactory;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

trait NodesTrait
{
    /**
     * @param string        $title
     * @param Translation   $translation
     * @param Node|null     $node
     * @param NodeType|null $type
     *
     * @return Node
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function createNode($title, Translation $translation, Node $node = null, NodeType $type = null)
    {
        /** @var NodeFactory $factory */
        $factory = $this->get(NodeFactory::class);
        $node = $factory->create($title, $type, $translation, $node);

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('em');
        $entityManager->flush();

        return $node;
    }

    /**
     * @param array $data
     * @param Node  $node
     *
     * @return NodeType|null
     */
    public function addStackType($data, Node $node)
    {
        if ($data['nodeId'] == $node->getId() &&
            !empty($data['nodeTypeId'])) {
            $nodeType = $this->get('em')->find(NodeType::class, (int) $data['nodeTypeId']);

            if (null !== $nodeType) {
                $node->addStackType($nodeType);
                $this->get('em')->flush();

                return $nodeType;
            }
        }

        return null;
    }

    /**
     * @param Node $node
     *
     * @return FormInterface|null
     */
    public function buildStackTypesForm(Node $node)
    {
        if ($node->isHidingChildren()) {
            $defaults = [];
            $builder = $this->createNamedFormBuilder('add_stack_type', $defaults)
                ->add('nodeId', HiddenType::class, [
                    'data' => (int) $node->getId(),
                ])
                ->add('nodeTypeId', NodeTypesType::class, [
                    'showInvisible' => true,
                    'label' => false,
                    'constraints' => [
                        new NotNull(),
                        new NotBlank(),
                    ],
                ]);

            return $builder->getForm();
        } else {
            return null;
        }
    }

    /**
     * @param Node $parentNode
     *
     * @return FormInterface
     */
    protected function buildAddChildForm(Node $parentNode = null)
    {
        $defaults = [];

        $builder = $this->createFormBuilder($defaults)
                        ->add('nodeName', TextType::class, [
                            'label' => 'nodeName',
                            'constraints' => [
                                new NotNull(),
                                new NotBlank(),
                                new UniqueNodeName(),
                            ],
                        ])
            ->add('nodeTypeId', NodeTypesType::class, [
                'label' => 'nodeType',
                'constraints' => [
                    new NotNull(),
                    new NotBlank(),
                ],
            ]);

        if (null !== $parentNode) {
            $builder->add('parentId', HiddenType::class, [
                'data' => (int) $parentNode->getId(),
                'constraints' => [
                    new NotNull(),
                    new NotBlank(),
                ],
            ]);
        }

        return $builder->getForm();
    }

    /**
     * @param Node $node
     *
     * @return FormInterface
     */
    protected function buildDeleteForm(Node $node)
    {
        $builder = $this->createNamedFormBuilder('remove_stack_type_'.$node->getId())
                        ->add('nodeId', HiddenType::class, [
                            'data' => $node->getId(),
                            'constraints' => [
                                new NotNull(),
                                new NotBlank(),
                            ],
                        ]);

        return $builder->getForm();
    }

    /**
     * @return FormInterface
     */
    protected function buildEmptyTrashForm()
    {
        $builder = $this->createFormBuilder();
        return $builder->getForm();
    }
}
