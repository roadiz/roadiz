<?php
declare(strict_types=1);

namespace Themes\Rozier\Forms;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\CMS\Forms\TagsType;
use RZ\Roadiz\Core\Entities\Node;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Themes\Rozier\Forms\DataTransformer\TagTransformer;

/**
 * Class NodeTagsType.
 *
 * @package Themes\Rozier\Forms
 */
class NodeTagsType extends AbstractType
{

    /**
     * {@inheritdoc}
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('tags', TagsType::class);
        $builder->get('tags')
            ->addModelTransformer(new TagTransformer($options['entityManager']));
    }

    /**
     * {@inheritdoc}
     *
     * @param OptionsResolver $optionsResolver
     */
    public function configureOptions(OptionsResolver $optionsResolver)
    {
        $optionsResolver->setRequired('entityManager');
        $optionsResolver->setDefault('data_class', Node::class);
        $optionsResolver->setAllowedTypes('entityManager', EntityManager::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'node_tags';
    }
}
