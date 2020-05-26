<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\NodeSource;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\CustomForm;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Handlers\NodeHandler;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class NodeSourceCustomFormType
 * @package RZ\Roadiz\CMS\Forms\NodeSource
 */
class NodeSourceCustomFormType extends AbstractNodeSourceFieldType
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
            'class' => CustomForm::class,
            'multiple' => true,
            'property' => 'id',
        ]);

        $resolver->setRequired('nodeHandler');
        $resolver->setAllowedTypes('nodeHandler', [NodeHandler::class]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'custom_forms';
    }

    /**
     * @param FormEvent $event
     */
    public function onPreSetData(FormEvent $event)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $event->getForm()->getConfig()->getOption('entityManager');

        /** @var NodesSources $nodeSource */
        $nodeSource = $event->getForm()->getConfig()->getOption('nodeSource');

        /** @var NodeTypeField $nodeTypeField */
        $nodeTypeField = $event->getForm()->getConfig()->getOption('nodeTypeField');

        $event->setData($entityManager
            ->getRepository(CustomForm::class)
            ->findByNodeAndField($nodeSource->getNode(), $nodeTypeField));
    }

    /**
     * @param FormEvent $event
     */
    public function onPostSubmit(FormEvent $event)
    {
        /** @var NodesSources $nodeSource */
        $nodeSource = $event->getForm()->getConfig()->getOption('nodeSource');

        /** @var EntityManager $entityManager */
        $entityManager = $event->getForm()->getConfig()->getOption('entityManager');

        /** @var NodeHandler $nodeHandler */
        $nodeHandler = $event->getForm()->getConfig()->getOption('nodeHandler');

        /** @var NodeTypeField $nodeTypeField */
        $nodeTypeField = $event->getForm()->getConfig()->getOption('nodeTypeField');

        $nodeHandler->setNode($nodeSource->getNode());
        $nodeHandler->cleanCustomFormsFromField($nodeTypeField, false);

        if (is_array($event->getData())) {
            $position = 0;
            foreach ($event->getData() as $customFormId) {
                /** @var CustomForm|null $tempCForm */
                $tempCForm = $entityManager->find(CustomForm::class, (int) $customFormId);

                if ($tempCForm !== null) {
                    $nodeHandler->addCustomFormForField($tempCForm, $nodeTypeField, false, $position);
                    $position++;
                } else {
                    throw new \RuntimeException('Custom form #'.$customFormId.' was not found during relationship creation.');
                }
            }
        }
    }
}
