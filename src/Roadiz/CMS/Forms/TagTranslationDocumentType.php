<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms;

use Doctrine\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use RZ\Roadiz\CMS\Forms\DataTransformer\TagTranslationDocumentsTransformer;
use RZ\Roadiz\Core\Entities\TagTranslation;
use RZ\Roadiz\Core\Entities\TagTranslationDocuments;
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
class TagTranslationDocumentType extends AbstractType
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
        $builder->addModelTransformer(new TagTranslationDocumentsTransformer(
            $options['entityManager'],
            $options['tagTranslation']
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
            'class' => TagTranslationDocuments::class,
        ]);

        $resolver->setRequired('tagTranslation');
        $resolver->setRequired('entityManager');
        $resolver->setAllowedTypes('tagTranslation', [TagTranslation::class]);
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
        if ($event->getForm()->getConfig()->getOption('tagTranslation') instanceof TagTranslation) {
            /** @var EntityManager $entityManager */
            $entityManager = $event->getForm()->getConfig()->getOption('entityManager');

            /** @var QueryBuilder $qb */
            $qb = $entityManager->getRepository(TagTranslationDocuments::class)->createQueryBuilder('ttd');
            $qb->delete()
                ->andWhere($qb->expr()->eq('ttd.tagTranslation', ':tagTranslation'))
                ->setParameter(':tagTranslation', $event->getForm()->getConfig()->getOption('tagTranslation'));
            $qb->getQuery()->execute();
        }
    }
}
