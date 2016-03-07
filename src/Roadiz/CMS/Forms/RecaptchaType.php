<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
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
 * @file RecaptchaType.php
 * @author Nikolay Georgiev <symfonist@gmail.com>
 */
namespace RZ\Roadiz\CMS\Forms;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * This class creates recaptcha element
 *
 * @author Nikolay Georgiev <symfonist@gmail.com>
 * @since 1.0
 */
class RecaptchaType extends AbstractType
{
    /**
     * @var array
     */
    protected $options = array();

    /**
     * Construct
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * (non-PHPdoc)
     * @see Symfony\Component\Form.AbstractType::buildView()
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['configs'] = $options['configs'];
    }

    /**
     * @see Symfony\Component\Form.AbstractType::configureOptions()
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $defaultConfigs = $this->options;

        $resolver->setDefaults(array(
            'configs' => $defaultConfigs,
        ));

        $resolver->setNormalizer(
            'configs',
            function (Options $options, $value) use ($defaultConfigs) {
                $configs = array_replace_recursive($defaultConfigs, $value);
                return $configs;
            }
        );
    }

    /**
     * @see Symfony\Component\Form.AbstractType::getParent()
     */
    public function getParent()
    {
        return 'text';
    }

    /**
     * @see Symfony\Component\Form.FormTypeInterface::getName()
     *
     *      {% block recaptcha_widget -%}
     *          <div class="g-recaptcha" data-sitekey="{{ configs.publicKey }}"></div>
     *      {%- endblock recaptcha_widget %}
     */
    public function getName()
    {
        return 'recaptcha';
    }
}
