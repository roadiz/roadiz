<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HelpAndGroupExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['help'] = $options['help'] ?? '';
        $view->vars['group'] = $options['group'] ?? '';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'help' => null,
            'group' => null,
        ]);

        $resolver->setAllowedTypes('help', ['null', 'string']);
        $resolver->setAllowedTypes('group', ['null', 'string']);
    }
}
