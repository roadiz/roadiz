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
 * @file ProviderItemType.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\CMS\Forms;

use RZ\Roadiz\CMS\Forms\DataTransformer\ExplorerProviderItemTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Themes\Rozier\Explorer\ExplorerItemInterface;
use Themes\Rozier\Explorer\ExplorerProviderInterface;

/**
 * Class ExplorerProviderItemType
 * @package RZ\Roadiz\CMS\Forms
 */
class ExplorerProviderItemType extends AbstractType
{
    /**
     * @var ExplorerProviderInterface
     */
    protected $explorerProvider;

    /**
     * ExplorerProviderItemType constructor.
     * @param ExplorerProviderInterface $explorerProvider
     */
    public function __construct(ExplorerProviderInterface $explorerProvider)
    {
        $this->explorerProvider = $explorerProvider;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new ExplorerProviderItemTransformer($this->explorerProvider));
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

        if ($options['max_length'] > 0) {
            $view->vars['attr']['data-max-length'] = $options['max_length'];
        }
        if ($options['min_length'] > 0) {
            $view->vars['attr']['data-min-length'] = $options['min_length'];
        }

        $view->vars['provider_class'] = get_class($this->explorerProvider);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'explorer_provider';
    }

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('max_length', 0);
        $resolver->setDefault('min_length', 0);
        $resolver->setAllowedTypes('max_length', ['int']);
        $resolver->setAllowedTypes('min_length', ['int']);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'hidden';
    }
}
