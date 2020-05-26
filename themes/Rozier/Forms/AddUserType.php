<?php
declare(strict_types=1);

namespace Themes\Rozier\Forms;

use Doctrine\Common\Collections\Collection;
use RZ\Roadiz\CMS\Forms\GroupsType;
use RZ\Roadiz\Core\Entities\Group;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Class AddUserType
 *
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
                'authorizationChecker' => $options['authorizationChecker'],
                'entityManager' => $options['em'],
            ])
        ;

        $builder->get('groups')->addModelTransformer(new CallbackTransformer(function ($modelToForm) {
            if ($modelToForm instanceof Collection) {
                $modelToForm = $modelToForm->toArray();
            }
            return array_map(function (Group $group) {
                return $group->getId();
            }, $modelToForm);
        }, function ($formToModels) use ($options) {
            if (count($formToModels) === 0) {
                return [];
            }
            return $options['em']->getRepository(Group::class)->findBy([
                'id' => $formToModels
            ]);
        }));
    }

    public function getBlockPrefix()
    {
        return 'add_user';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setRequired('authorizationChecker');
        $resolver->setAllowedTypes('authorizationChecker', [AuthorizationCheckerInterface::class]);
    }
}
