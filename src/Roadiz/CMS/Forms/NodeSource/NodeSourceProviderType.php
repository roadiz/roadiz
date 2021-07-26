<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\NodeSource;

use Doctrine\Persistence\ManagerRegistry;
use Pimple\Container;
use RZ\Roadiz\CMS\Forms\DataTransformer\ProviderDataTransformer;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Themes\Rozier\Explorer\AbstractExplorerItem;
use Themes\Rozier\Explorer\AbstractExplorerProvider;
use Themes\Rozier\Explorer\ExplorerProviderInterface;

final class NodeSourceProviderType extends AbstractConfigurableNodeSourceFieldType
{
    protected Container $container;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param Container $container
     */
    public function __construct(ManagerRegistry $managerRegistry, Container $container)
    {
        parent::__construct($managerRegistry);
        $this->container = $container;
    }

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
            if ($nodeTypeField->isMultipleProvider()) {
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

        $builder->addModelTransformer(
            new ProviderDataTransformer(
                $options['nodeTypeField'],
                $this->getProvider($configuration, $options)
            )
        );
    }

    protected function getProvider(array $configuration, array $options): ExplorerProviderInterface
    {
        if ($this->container->offsetExists($configuration['classname'])) {
            return $this->container->offsetGet($configuration['classname']);
        } else {
            /** @var AbstractExplorerProvider $provider */
            $provider = new $configuration['classname'];
            $provider->setContainer($this->container);
            return $provider;
        }
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
        if (isset($configuration['options'])) {
            $providerOptions = $configuration['options'];
        } else {
            $providerOptions = [];
        }

        $provider = $this->getProvider($configuration, $options);

        $displayableData = [];
        $ids = call_user_func([$options['nodeSource'], $options['nodeTypeField']->getGetterName()]);
        if (!is_array($ids)) {
            $entities = $provider->getItemsById([$ids]);
        } else {
            $entities = $provider->getItemsById($ids);
        }

        if (is_array($entities)) {
            /** @var AbstractExplorerItem $entity */
            foreach ($entities as $entity) {
                $displayableData[] = $entity->toArray();
            }
        }

        $view->vars['data'] = $displayableData;

        if (isset($options['max_length']) && $options['max_length'] > 0) {
            $view->vars['attr']['data-max-length'] = $options['max_length'];
        }
        if (isset($options['min_length']) && $options['min_length'] > 0) {
            $view->vars['attr']['data-min-length'] = $options['min_length'];
        }

        $view->vars['provider_class'] = $configuration['classname'];

        if (is_array($providerOptions) && count($providerOptions) > 0) {
            $view->vars['provider_options'] = [];
            foreach ($providerOptions as $providerOption) {
                $view->vars['provider_options'][$providerOption['name']] = $providerOption['value'];
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'provider';
    }
}
