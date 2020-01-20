<?php
/**
 * Copyright (c) 2017. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file NodeSourceProviderType.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\CMS\Forms\NodeSource;

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

class NodeSourceProviderType extends AbstractConfigurableNodeSourceFieldType
{
    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('multiple', false);
        $resolver->setRequired('container');
        $resolver->setAllowedTypes('container', [Container::class]);
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
        /** @var Container $container */
        $container = $options['container'];
        if ($container->offsetExists($configuration['classname'])) {
            return $container->offsetGet($configuration['classname']);
        } else {
            /** @var AbstractExplorerProvider $provider */
            $provider = new $configuration['classname'];
            $provider->setContainer($options['container']);
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
