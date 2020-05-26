<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Theme selector form field type.
 */
class ThemesType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => [],
        ]);
        $resolver->setRequired('themes_config');
        $resolver->setAllowedTypes('themes_config', 'array');
        $resolver->setNormalizer('choices', function (Options $options, $value) {
            $value = [];
            foreach ($options['themes_config'] as $themeConfig) {
                $class = $themeConfig['classname'];
                $value[call_user_func([$class, 'getThemeName'])] = $class;
            }
            return $value;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'classname';
    }
}
