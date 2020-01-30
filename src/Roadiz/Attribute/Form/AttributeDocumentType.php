<?php
namespace RZ\Roadiz\Attribute\Form;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectManager;
use RZ\Roadiz\Attribute\Form\DataTransformer\AttributeDocumentsTransformer;
use RZ\Roadiz\Attribute\Model\AttributeInterface;
use RZ\Roadiz\Core\Entities\AttributeDocuments;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class NodeSourceDocumentType
 * @package RZ\Roadiz\CMS\Forms\NodeSource
 */
class AttributeDocumentType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            [$this, 'onPostSubmit']
        );
        $builder->addModelTransformer(new AttributeDocumentsTransformer(
            $options['entityManager'],
            $options['attribute']
        ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'required' => false,
            'allow_add' => true,
            'allow_delete' => true,
            'class' => AttributeDocuments::class,
        ]);

        $resolver->setRequired('attribute');
        $resolver->setRequired('entityManager');
        $resolver->setAllowedTypes('attribute', [AttributeInterface::class]);
        $resolver->setAllowedTypes('entityManager', [ObjectManager::class]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'documents';
    }

    /**
     * @inheritDoc
     */
    public function getParent()
    {
        return CollectionType::class;
    }

    /**
     * Delete existing document association.
     *
     * @param FormEvent $event
     */
    public function onPostSubmit(FormEvent $event)
    {
        if ($event->getForm()->getConfig()->getOption('attribute') instanceof AttributeInterface) {
            /** @var EntityManager $entityManager */
            $entityManager = $event->getForm()->getConfig()->getOption('entityManager');

            /** @var QueryBuilder $qb */
            $qb = $entityManager->getRepository(AttributeDocuments::class)->createQueryBuilder('ad');
            $qb->delete()
                ->andWhere($qb->expr()->eq('ad.attribute', ':attribute'))
                ->setParameter(':attribute', $event->getForm()->getConfig()->getOption('attribute'));
            $qb->getQuery()->execute();
        }
    }
}
