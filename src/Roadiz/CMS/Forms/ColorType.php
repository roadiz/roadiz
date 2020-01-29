<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms;

use RZ\Roadiz\CMS\Forms\Constraints\HexadecimalColor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ColorType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('attr', [
            'class' => 'colorpicker-input',
        ]);
        $resolver->setDefault('required', false);
        $resolver->setDefault('constraints', [
            new HexadecimalColor(),
        ]);
    }

    public function getBlockPrefix()
    {
        return 'rz_color';
    }

    public function getParent()
    {
        return TextType::class;
    }
}
