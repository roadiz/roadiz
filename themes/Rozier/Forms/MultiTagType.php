<?php
declare(strict_types=1);

namespace Themes\Rozier\Forms;

use RZ\Roadiz\CMS\Forms\Constraints\UniqueTagName;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class MultiTagType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('names', TextareaType::class, [
            'label' => 'tags.names',
            'attr' => [
                'placeholder' => 'write.every.tags.names.comma.separated',
            ],
            'constraints' => [
                new NotNull(),
                new NotBlank(),
                new UniqueTagName(),
            ],
        ]);
    }

    public function getBlockPrefix()
    {
        return 'multitags';
    }
}
