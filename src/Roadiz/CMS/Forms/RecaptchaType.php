<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
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
     * (non-PHPdoc)
     * @see \Symfony\Component\Form\AbstractType::buildView()
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['configs'] = $options['configs'];
    }

    /**
     * @see \Symfony\Component\Form\AbstractType::configureOptions()
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'configs' => [
                'publicKey' => ''
            ],
        ]);
    }

    /**
     * @see \Symfony\Component\Form\AbstractType::getParent()
     */
    public function getParent()
    {
        return TextType::class;
    }

    /**
     * @see \Symfony\Component\Form\FormTypeInterface::getName()
     *
     *      {% block recaptcha_widget -%}
     *          <div class="g-recaptcha" data-sitekey="{{ configs.publicKey }}"></div>
     *      {%- endblock recaptcha_widget %}
     */
    public function getBlockPrefix()
    {
        return 'recaptcha';
    }
}
