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
use RZ\Roadiz\Core\Entities\NodeTypeField;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Yaml\Yaml;
use Themes\Rozier\Explorer\AbstractExplorerItem;
use Themes\Rozier\Explorer\AbstractExplorerProvider;

class NodeSourceProviderType extends AbstractNodeSourceFieldType
{
    /**
     * @var string
     */
    private $classname;

    /**
     * @var AbstractExplorerProvider
     */
    private $provider;

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setRequired('container');
        $resolver->setAllowedTypes('container', [Container::class]);
    }


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['nodeTypeField']->getType() === NodeTypeField::MULTI_PROVIDER_T ||
            $options['nodeTypeField']->getType() === NodeTypeField::SINGLE_PROVIDER_T) {
            $configuration = Yaml::parse($options['nodeTypeField']->getDefaultValues());
            $this->classname = $configuration['classname'];
            $this->provider = new $configuration['classname'];
            $this->provider->setContainer($options['container']);
        }

        $builder->addModelTransformer(new CallbackTransformer(
            function ($entitiesToForm) use ($options) {
                if ($options['nodeTypeField']->getType() === NodeTypeField::MULTI_PROVIDER_T && is_array($entitiesToForm)) {
                    if (count($entitiesToForm) > 0) {
                        return $this->provider->getItemsById($entitiesToForm);
                    }
                    return [];
                }
                if ($options['nodeTypeField']->getType() === NodeTypeField::SINGLE_PROVIDER_T) {
                    if (isset($entitiesToForm)) {
                        return $this->provider->getItemsById($entitiesToForm);
                    }
                }
                return null;
            },
            function ($formToEntities) use ($options) {
                if (is_array($formToEntities) && $options['nodeTypeField']->isSingleProvider()) {
                    return $formToEntities[0];
                }
                return $formToEntities;
            }
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

        $displayableData = [];
        $ids = call_user_func([$options['nodeSource'], $options['nodeTypeField']->getGetterName()]);
        if (!is_array($ids)) {
            $entities = $this->provider->getItemsById([$ids]);
        } else {
            $entities = $this->provider->getItemsById($ids);
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

        $view->vars['provider_class'] = $this->classname;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'provider';
    }

    /**
     * @inheritDoc
     */
    public function getBlockPrefix()
    {
        return 'provider';
    }
}
