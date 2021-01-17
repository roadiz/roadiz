<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\NodeSource;

use Doctrine\Persistence\Proxy;
use RZ\Roadiz\CMS\Forms\DataTransformer\JoinDataTransformer;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class NodeSourceJoinType extends AbstractConfigurableNodeSourceFieldType
{
    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('multiple', false);
        $resolver->setAllowedTypes('multiple', ['bool']);
        $resolver->setNormalizer('multiple', function (Options $options) {
            /** @var NodeTypeField $nodeTypeField */
            $nodeTypeField = $options['nodeTypeField'];
            if ($nodeTypeField->isManyToMany()) {
                return true;
            }
            return false;
        });
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $configuration = $this->getFieldConfiguration($options);

        $builder->addModelTransformer(new JoinDataTransformer(
            $options['nodeTypeField'],
            $this->entityManager,
            $configuration['classname']
        ));
    }

    /**
     * Pass data to form twig template.
     *
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $configuration = $this->getFieldConfiguration($options);
        $displayableData = [];

        $entities = call_user_func([$options['nodeSource'], $options['nodeTypeField']->getGetterName()]);

        if ($entities instanceof \Traversable) {
            /** @var AbstractEntity $entity */
            foreach ($entities as $entity) {
                if ($entity instanceof Proxy) {
                    $entity->__load();
                }
                $data = [
                    'id' => $entity->getId(),
                    'classname' => $configuration['classname'],
                ];
                if (is_callable([$entity, $configuration['displayable']])) {
                    $data['name'] = call_user_func([$entity, $configuration['displayable']]);
                }
                $displayableData[] = $data;
            }
        } elseif ($entities instanceof AbstractEntity) {
            if ($entities instanceof Proxy) {
                $entities->__load();
            }
            $data = [
                'id' => $entities->getId(),
                'classname' => $configuration['classname'],
            ];
            if (is_callable([$entities, $configuration['displayable']])) {
                $data['name'] = call_user_func([$entities, $configuration['displayable']]);
            }
            $displayableData[] = $data;
        }

        $view->vars['data'] = $displayableData;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'join';
    }
}
