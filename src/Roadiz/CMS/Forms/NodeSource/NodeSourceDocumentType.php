<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\NodeSource;

use Doctrine\Persistence\ManagerRegistry;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Handlers\NodesSourcesHandler;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @package RZ\Roadiz\CMS\Forms\NodeSource
 */
final class NodeSourceDocumentType extends AbstractNodeSourceFieldType
{
    protected NodesSourcesHandler $nodesSourcesHandler;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param NodesSourcesHandler $nodesSourcesHandler
     */
    public function __construct(ManagerRegistry $managerRegistry, NodesSourcesHandler $nodesSourcesHandler)
    {
        parent::__construct($managerRegistry);
        $this->nodesSourcesHandler = $nodesSourcesHandler;
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
            'class' => Document::class,
            'multiple' => true,
            'property' => 'id',
        ]);

        $resolver->setRequired([
            'label',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'documents';
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

        $event->setData($this->managerRegistry
            ->getRepository(Document::class)
            ->findByNodeSourceAndField(
                $nodeSource,
                $nodeTypeField
            ));
    }

    /**
     * @param FormEvent $event
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function onPostSubmit(FormEvent $event)
    {
        /** @var NodesSources $nodeSource */
        $nodeSource = $event->getForm()->getConfig()->getOption('nodeSource');
        /** @var NodeTypeField $nodeTypeField */
        $nodeTypeField = $event->getForm()->getConfig()->getOption('nodeTypeField');

        $this->nodesSourcesHandler->setNodeSource($nodeSource);
        $this->nodesSourcesHandler->cleanDocumentsFromField($nodeTypeField, false);

        if (is_array($event->getData())) {
            $position = 0;
            $manager = $this->managerRegistry->getManagerForClass(Document::class);
            foreach ($event->getData() as $documentId) {
                /** @var Document|null $tempDoc */
                $tempDoc = $manager->find(Document::class, (int) $documentId);

                if ($tempDoc !== null) {
                    $this->nodesSourcesHandler->addDocumentForField($tempDoc, $nodeTypeField, false, $position);
                    $position++;
                } else {
                    throw new \RuntimeException('Document #'.$documentId.' was not found during relationship creation.');
                }
            }
        }
    }
}
