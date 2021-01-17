<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\NodeSource;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Handlers\NodeHandler;
use RZ\Roadiz\Core\Repositories\NodeRepository;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @package RZ\Roadiz\CMS\Forms\NodeSource
 */
final class NodeSourceNodeType extends AbstractNodeSourceFieldType
{
    /**
     * @var NodeHandler
     */
    protected $nodeHandler;

    /**
     * @param EntityManagerInterface $entityManager
     * @param NodeHandler $nodeHandler
     */
    public function __construct(EntityManagerInterface $entityManager, NodeHandler $nodeHandler)
    {
        parent::__construct($entityManager);
        $this->nodeHandler = $nodeHandler;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            [$this, 'onPreSetData']
        )
            ->addEventListener(
                FormEvents::POST_SUBMIT,
                [$this, 'onPostSubmit']
            )
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'required' => false,
            'mapped' => false,
            'class' => Node::class,
            'multiple' => true,
            'property' => 'id',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'nodes';
    }

    /**
     * @param FormEvent $event
     */
    public function onPreSetData(FormEvent $event)
    {
        /** @var NodesSources $nodeSource */
        $nodeSource = $event->getForm()->getConfig()->getOption('nodeSource');

        /** @var NodeTypeField $nodeTypeField */
        $nodeTypeField = $event->getForm()->getConfig()->getOption('nodeTypeField');

        /** @var NodeRepository $nodeRepo */
        $nodeRepo = $this->entityManager
            ->getRepository(Node::class)
            ->setDisplayingNotPublishedNodes(true);
        $event->setData($nodeRepo->findByNodeAndField(
            $nodeSource->getNode(),
            $nodeTypeField
        ));
    }

    /**
     * @param FormEvent $event
     */
    public function onPostSubmit(FormEvent $event)
    {
        /** @var NodesSources $nodeSource */
        $nodeSource = $event->getForm()->getConfig()->getOption('nodeSource');

        /** @var NodeTypeField $nodeTypeField */
        $nodeTypeField = $event->getForm()->getConfig()->getOption('nodeTypeField');

        $this->nodeHandler->setNode($nodeSource->getNode());
        $this->nodeHandler->cleanNodesFromField($nodeTypeField, false);

        if (is_array($event->getData())) {
            $position = 0;
            foreach ($event->getData() as $nodeId) {
                /** @var Node|null $tempNode */
                $tempNode = $this->entityManager->find(Node::class, (int) $nodeId);

                if ($tempNode !== null) {
                    $this->nodeHandler->addNodeForField($tempNode, $nodeTypeField, false, $position);
                    $position++;
                } else {
                    throw new \RuntimeException('Node #'.$nodeId.' was not found during relationship creation.');
                }
            }
        }
    }
}
