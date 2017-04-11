<?php

namespace Themes\Rozier\Forms;


use RZ\Roadiz\CMS\Forms\TagsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Themes\Rozier\Forms\DataTransformer\TagTransformer;

class NodeTagsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('tags', new TagsType());
        $builder->get('tags')
            ->addModelTransformer(new TagTransformer($options['entityManager']));
    }

    public function configureOptions(OptionsResolver $optionsResolver)
    {
        $optionsResolver->setRequired('entityManager');
        $optionsResolver->setDefault('data_class', 'RZ\Roadiz\Core\Entities\Node');
        $optionsResolver->setAllowedTypes('entityManager', 'Doctrine\ORM\EntityManager');
    }

    public function getName()
    {
        return 'node_tags';
    }
}
