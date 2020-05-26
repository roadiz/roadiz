<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\NodeSource;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Handlers\NodesSourcesHandler;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class NodeSourceDocumentType
 * @package RZ\Roadiz\CMS\Forms\NodeSource
 */
class NodeSourceDocumentType extends AbstractNodeSourceFieldType
{
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
            'nodeSourceHandler'
        ]);

        $resolver->setAllowedTypes('nodeSourceHandler', [NodesSourcesHandler::class]);
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
        /** @var EntityManager $entityManager */
        $entityManager = $event->getForm()->getConfig()->getOption('entityManager');

        $event->setData($entityManager
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
        /** @var NodesSourcesHandler $nodesSourcesHandler */
        $nodesSourcesHandler = $event->getForm()->getConfig()->getOption('nodeSourceHandler');
        /** @var NodeTypeField $nodeTypeField */
        $nodeTypeField = $event->getForm()->getConfig()->getOption('nodeTypeField');
        /** @var EntityManager $entityManager */
        $entityManager = $event->getForm()->getConfig()->getOption('entityManager');

        $nodesSourcesHandler->setNodeSource($nodeSource);
        $nodesSourcesHandler->cleanDocumentsFromField($nodeTypeField, false);

        if (is_array($event->getData())) {
            $position = 0;
            foreach ($event->getData() as $documentId) {
                /** @var Document|null $tempDoc */
                $tempDoc = $entityManager->find(Document::class, (int) $documentId);

                if ($tempDoc !== null) {
                    $nodesSourcesHandler->addDocumentForField($tempDoc, $nodeTypeField, false, $position);
                    $position++;
                } else {
                    throw new \RuntimeException('Document #'.$documentId.' was not found during relationship creation.');
                }
            }
        }
    }
}
