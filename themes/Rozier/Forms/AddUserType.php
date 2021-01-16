<?php
declare(strict_types=1);

namespace Themes\Rozier\Forms;

use RZ\Roadiz\CMS\Forms\GroupsType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @package Themes\Rozier\Forms
 */
class AddUserType extends UserType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('groups', GroupsType::class, [
                'label' => 'user.groups',
                'required' => false,
                'multiple' => true,
                'expanded' => true,
            ])
        ;
    }

    public function getBlockPrefix()
    {
        return 'add_user';
    }
}
